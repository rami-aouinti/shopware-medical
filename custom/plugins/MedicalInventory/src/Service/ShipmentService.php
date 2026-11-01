<?php declare(strict_types=1);

namespace MedicalInventory\Service;

use Doctrine\DBAL\Connection;
use DomainException;
use InvalidArgumentException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;

final class ShipmentService
{
    public function __construct(
        private readonly Connection $connection,
        private readonly WarehouseStockService $stockService,
    ) {
    }

    /**
     * @param list<array{productId: string, quantity: int}> $items
     */
    public function createShipment(
        string $orderId,
        string $warehouseCode,
        array $items,
        Context $context,
        ?string $carrierCode = null,
        ?string $trackingNumber = null,
    ): string {
        if (!Uuid::isValid($orderId) || $items === []) {
            throw new InvalidArgumentException(
                'A valid order ID and at least one shipment item are required.'
            );
        }

        $quantitiesByProduct = [];

        foreach ($items as $item) {
            if (!Uuid::isValid($item['productId']) || $item['quantity'] <= 0) {
                throw new InvalidArgumentException(
                    'Shipment product ID and quantity must be valid and positive.'
                );
            }

            $quantitiesByProduct[$item['productId']] = (
                $quantitiesByProduct[$item['productId']] ?? 0
            ) + $item['quantity'];
        }

        $normalizedItems = [];

        foreach ($quantitiesByProduct as $productId => $quantity) {
            $normalizedItems[] = ['productId' => $productId, 'quantity' => $quantity];
        }

        return $this->connection->transactional(function () use (
            $orderId,
            $warehouseCode,
            $normalizedItems,
            $context,
            $carrierCode,
            $trackingNumber
        ): string {
            $warehouseId = $this->getWarehouseId($warehouseCode);
            $shipmentId = Uuid::randomHex();
            $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s.v');

            $this->connection->insert('medical_shipment', [
                'id' => Uuid::fromHexToBytes($shipmentId),
                'order_id' => Uuid::fromHexToBytes($orderId),
                'order_version_id' => Uuid::fromHexToBytes(\Shopware\Core\Defaults::LIVE_VERSION),
                'warehouse_id' => Uuid::fromHexToBytes($warehouseId),
                'carrier_code' => $carrierCode,
                'tracking_number' => $trackingNumber,
                'status' => 'SHIPPED',
                'shipped_at' => $now,
                'created_at' => $now,
            ]);

            foreach ($normalizedItems as $item) {
                $this->shipAllocation(
                    $orderId,
                    $warehouseId,
                    $item['productId'],
                    $item['quantity'],
                    $shipmentId,
                    $context,
                    $now
                );
            }

            $this->recordEvent($shipmentId, 'SHIPPED', 'Shipment created', null, $now);

            return $shipmentId;
        });
    }

    public function markDelivered(string $shipmentId, ?string $location = null): void
    {
        if (!Uuid::isValid($shipmentId)) {
            throw new InvalidArgumentException('Shipment ID must be a valid UUID.');
        }

        $this->connection->transactional(function () use ($shipmentId, $location): void {
            $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s.v');
            $affected = $this->connection->update('medical_shipment', [
                'status' => 'DELIVERED',
                'delivered_at' => $now,
                'updated_at' => $now,
            ], [
                'id' => Uuid::fromHexToBytes($shipmentId),
                'status' => 'SHIPPED',
            ]);

            if ($affected !== 1) {
                throw new DomainException('Only a shipped shipment can be marked as delivered.');
            }

            $this->recordEvent($shipmentId, 'DELIVERED', 'Shipment delivered', $location, $now);
        });
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listByOrder(string $orderId): array
    {
        if (!Uuid::isValid($orderId)) {
            throw new InvalidArgumentException('Order ID must be a valid UUID.');
        }

        return $this->connection->fetchAllAssociative(
            <<<'SQL'
                SELECT
                    LOWER(HEX(s.id)) AS id,
                    w.code AS warehouse_code,
                    s.carrier_code,
                    s.tracking_number,
                    s.status,
                    s.shipped_at,
                    s.delivered_at
                FROM medical_shipment s
                INNER JOIN medical_warehouse w ON w.id = s.warehouse_id
                WHERE s.order_id = :orderId
                ORDER BY s.created_at ASC
            SQL,
            ['orderId' => Uuid::fromHexToBytes($orderId)]
        );
    }

    private function shipAllocation(
        string $orderId,
        string $warehouseId,
        string $productId,
        int $quantity,
        string $shipmentId,
        Context $context,
        string $now,
    ): void {
        if (!Uuid::isValid($productId) || $quantity <= 0) {
            throw new InvalidArgumentException(
                'Shipment product ID and quantity must be valid and positive.'
            );
        }

        $allocation = $this->findShippableAllocation($orderId, $warehouseId, $productId);
        $this->assertAllocationCanShip($allocation, $quantity);

        $allocationId = (string) $allocation['id'];
        $reservation = $this->findActiveReservation($allocationId);
        $this->assertReservationCanShip($reservation, $quantity);

        $this->stockService->shipReservedStock(
            $this->getWarehouseCode($warehouseId),
            $productId,
            $quantity,
            $context,
            'shipment',
            $shipmentId
        );

        $this->updateAllocationAfterShipment($allocation, $quantity, $now);
        $this->updateReservationAfterShipment($reservation, $quantity, $now);
        $this->createShipmentItem($shipmentId, $allocationId, $productId, $quantity, $now);
    }

    /**
     * @return array<string, mixed>
     */
    private function findShippableAllocation(string $orderId, string $warehouseId, string $productId): array
    {
        $allocation = $this->connection->fetchAssociative(
            <<<'SQL'
                SELECT
                    LOWER(HEX(id)) AS id,
                    quantity,
                    shipped_quantity
                FROM medical_order_warehouse_allocation
                WHERE order_id = :orderId
                  AND warehouse_id = :warehouseId
                  AND product_id = :productId
                  AND status IN ('RESERVED', 'PARTIALLY_SHIPPED')
                FOR UPDATE
            SQL,
            [
                'orderId' => Uuid::fromHexToBytes($orderId),
                'warehouseId' => Uuid::fromHexToBytes($warehouseId),
                'productId' => Uuid::fromHexToBytes($productId),
            ]
        );

        if ($allocation === false) {
            throw new DomainException('No shippable allocation exists for this product in the selected warehouse.');
        }

        return $allocation;
    }

    /**
     * @param array<string, mixed> $allocation
     */
    private function assertAllocationCanShip(array $allocation, int $quantity): void
    {
        $remaining = (int) $allocation['quantity'] - (int) $allocation['shipped_quantity'];

        if ($quantity > $remaining) {
            throw new DomainException(
                sprintf('Cannot ship %d units; only %d remain allocated.', $quantity, $remaining)
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function findActiveReservation(string $allocationId): array
    {
        $reservation = $this->connection->fetchAssociative(
            <<<'SQL'
                SELECT LOWER(HEX(id)) AS id, quantity, consumed_quantity
                FROM medical_stock_reservation
                WHERE allocation_id = :allocationId
                  AND status = 'ACTIVE'
                FOR UPDATE
            SQL,
            ['allocationId' => Uuid::fromHexToBytes($allocationId)]
        );

        if ($reservation === false) {
            throw new DomainException('The stock reservation is missing or smaller than the requested shipment.');
        }

        return $reservation;
    }

    /**
     * @param array<string, mixed> $reservation
     */
    private function assertReservationCanShip(array $reservation, int $quantity): void
    {
        $remaining = (int) $reservation['quantity'] - (int) $reservation['consumed_quantity'];

        if ($remaining < $quantity) {
            throw new DomainException('The stock reservation is missing or smaller than the requested shipment.');
        }
    }

    /**
     * @param array<string, mixed> $allocation
     */
    private function updateAllocationAfterShipment(array $allocation, int $quantity, string $now): void
    {
        $shippedQuantity = (int) $allocation['shipped_quantity'] + $quantity;
        $status = $shippedQuantity === (int) $allocation['quantity'] ? 'SHIPPED' : 'PARTIALLY_SHIPPED';

        $this->connection->update('medical_order_warehouse_allocation', [
            'shipped_quantity' => $shippedQuantity,
            'status' => $status,
            'updated_at' => $now,
        ], ['id' => Uuid::fromHexToBytes((string) $allocation['id'])]);
    }

    /**
     * @param array<string, mixed> $reservation
     */
    private function updateReservationAfterShipment(array $reservation, int $quantity, string $now): void
    {
        $consumedQuantity = (int) $reservation['consumed_quantity'] + $quantity;
        $status = $consumedQuantity === (int) $reservation['quantity'] ? 'CONSUMED' : 'ACTIVE';

        $this->connection->update('medical_stock_reservation', [
            'consumed_quantity' => $consumedQuantity,
            'status' => $status,
            'updated_at' => $now,
        ], ['id' => Uuid::fromHexToBytes((string) $reservation['id'])]);
    }

    private function createShipmentItem(
        string $shipmentId,
        string $allocationId,
        string $productId,
        int $quantity,
        string $now,
    ): void {
        $this->connection->insert('medical_shipment_item', [
            'id' => Uuid::fromHexToBytes(Uuid::randomHex()),
            'shipment_id' => Uuid::fromHexToBytes($shipmentId),
            'allocation_id' => Uuid::fromHexToBytes($allocationId),
            'product_id' => Uuid::fromHexToBytes($productId),
            'quantity' => $quantity,
            'created_at' => $now,
        ]);
    }

    private function getWarehouseId(string $warehouseCode): string
    {
        $id = $this->connection->fetchOne(
            'SELECT LOWER(HEX(id)) FROM medical_warehouse WHERE code = :code',
            ['code' => strtoupper(trim($warehouseCode))]
        );

        if (!is_string($id) || !Uuid::isValid($id)) {
            throw new DomainException(sprintf('Warehouse "%s" was not found.', $warehouseCode));
        }

        return $id;
    }

    private function getWarehouseCode(string $warehouseId): string
    {
        $code = $this->connection->fetchOne(
            'SELECT code FROM medical_warehouse WHERE id = :id',
            ['id' => Uuid::fromHexToBytes($warehouseId)]
        );

        if (!is_string($code)) {
            throw new DomainException('The shipment warehouse no longer exists.');
        }

        return $code;
    }

    private function recordEvent(
        string $shipmentId,
        string $eventCode,
        string $description,
        ?string $location,
        string $now,
    ): void {
        $this->connection->insert('medical_shipment_event', [
            'id' => Uuid::fromHexToBytes(Uuid::randomHex()),
            'shipment_id' => Uuid::fromHexToBytes($shipmentId),
            'event_code' => $eventCode,
            'description' => $description,
            'location' => $location,
            'event_at' => $now,
            'created_at' => $now,
        ]);
    }
}
