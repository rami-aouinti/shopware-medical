<?php declare(strict_types=1);

namespace MedicalInventory\Service;

use Doctrine\DBAL\Connection;
use DomainException;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;

final class OrderFulfillmentService
{
    public function __construct(
        private readonly Connection $connection,
        private readonly WarehouseAllocationService $allocationService,
        private readonly WarehouseStockService $stockService,
    ) {
    }

    public function reserveOrder(string $orderId, Cart $cart, Context $context): void
    {
        $existing = (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM medical_order_warehouse_allocation WHERE order_id = :orderId',
            ['orderId' => Uuid::fromHexToBytes($orderId)]
        );

        if ($existing > 0) {
            return;
        }

        foreach ($this->getProductQuantities($cart) as $productId => $quantity) {
            $managedStockRows = (int) $this->connection->fetchOne(
                <<<'SQL'
                    SELECT COUNT(*)
                    FROM medical_warehouse_stock
                    WHERE product_id = :productId
                      AND product_version_id = :productVersionId
                SQL,
                [
                    'productId' => Uuid::fromHexToBytes($productId),
                    'productVersionId' => Uuid::fromHexToBytes($context->getVersionId()),
                ]
            );

            // Products without MedicalInventory stock rows remain managed by native Shopware stock.
            if ($managedStockRows === 0) {
                continue;
            }

            $plan = $this->allocationService->planForProduct($productId, $quantity, $context);

            foreach ($plan as $item) {
                $warehouseId = $this->getWarehouseId($item['warehouseCode']);
                $this->stockService->reserveStock(
                    $item['warehouseCode'],
                    $productId,
                    $item['quantity'],
                    $context,
                    'order',
                    $orderId
                );

                $allocationId = Uuid::randomHex();
                $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s.v');

                $this->connection->insert('medical_order_warehouse_allocation', [
                    'id' => Uuid::fromHexToBytes($allocationId),
                    'order_id' => Uuid::fromHexToBytes($orderId),
                    'order_version_id' => Uuid::fromHexToBytes(Defaults::LIVE_VERSION),
                    'product_id' => Uuid::fromHexToBytes($productId),
                    'product_version_id' => Uuid::fromHexToBytes($context->getVersionId()),
                    'warehouse_id' => Uuid::fromHexToBytes($warehouseId),
                    'quantity' => $item['quantity'],
                    'shipped_quantity' => 0,
                    'status' => 'RESERVED',
                    'created_at' => $now,
                ]);

                $this->connection->insert('medical_stock_reservation', [
                    'id' => Uuid::fromHexToBytes(Uuid::randomHex()),
                    'allocation_id' => Uuid::fromHexToBytes($allocationId),
                    'order_id' => Uuid::fromHexToBytes($orderId),
                    'order_version_id' => Uuid::fromHexToBytes(Defaults::LIVE_VERSION),
                    'warehouse_id' => Uuid::fromHexToBytes($warehouseId),
                    'product_id' => Uuid::fromHexToBytes($productId),
                    'product_version_id' => Uuid::fromHexToBytes($context->getVersionId()),
                    'quantity' => $item['quantity'],
                    'consumed_quantity' => 0,
                    'status' => 'ACTIVE',
                    'created_at' => $now,
                ]);
            }
        }
    }

    public function releaseOrder(string $orderId, Context $context): void
    {
        $reservations = $this->connection->fetchAllAssociative(
            <<<'SQL'
                SELECT
                    LOWER(HEX(id)) AS id,
                    LOWER(HEX(warehouse_id)) AS warehouse_id,
                    LOWER(HEX(product_id)) AS product_id,
                    quantity,
                    consumed_quantity
                FROM medical_stock_reservation
                WHERE order_id = :orderId
                  AND status = 'ACTIVE'
                FOR UPDATE
            SQL,
            ['orderId' => Uuid::fromHexToBytes($orderId)]
        );

        foreach ($reservations as $reservation) {
            $remaining = (int) $reservation['quantity'] - (int) $reservation['consumed_quantity'];

            if ($remaining > 0) {
                $warehouseCode = $this->getWarehouseCode((string) $reservation['warehouse_id']);
                $this->stockService->releaseReservation(
                    $warehouseCode,
                    (string) $reservation['product_id'],
                    $remaining,
                    $context,
                    'order',
                    $orderId
                );
            }

            $this->connection->update('medical_stock_reservation', [
                'status' => 'RELEASED',
                'released_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s.v'),
                'updated_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s.v'),
            ], ['id' => Uuid::fromHexToBytes((string) $reservation['id'])]);
        }

        $this->connection->executeStatement(
            <<<'SQL'
                UPDATE medical_order_warehouse_allocation
                SET status = CASE WHEN shipped_quantity > 0 THEN 'PARTIALLY_CANCELLED' ELSE 'RELEASED' END,
                    updated_at = :updatedAt
                WHERE order_id = :orderId
                  AND status IN ('RESERVED', 'PARTIALLY_SHIPPED')
            SQL,
            [
                'updatedAt' => (new \DateTimeImmutable())->format('Y-m-d H:i:s.v'),
                'orderId' => Uuid::fromHexToBytes($orderId),
            ]
        );

        $productIds = $this->connection->fetchFirstColumn(
            <<<'SQL'
                SELECT DISTINCT LOWER(HEX(product_id))
                FROM medical_order_warehouse_allocation
                WHERE order_id = :orderId
            SQL,
            ['orderId' => Uuid::fromHexToBytes($orderId)]
        );

        foreach ($productIds as $productId) {
            if (is_string($productId)) {
                $this->stockService->syncProductAvailability($productId, $context);
            }
        }
    }

    /**
     * @return array<string, int>
     */
    private function getProductQuantities(Cart $cart): array
    {
        $quantities = [];

        foreach ($cart->getLineItems()->getFlat() as $lineItem) {
            if ($lineItem->getType() !== LineItem::PRODUCT_LINE_ITEM_TYPE) {
                continue;
            }

            $productId = $lineItem->getReferencedId();

            if (!is_string($productId) || !Uuid::isValid($productId)) {
                continue;
            }

            $quantities[$productId] = ($quantities[$productId] ?? 0) + $lineItem->getQuantity();
        }

        return $quantities;
    }

    private function getWarehouseId(string $warehouseCode): string
    {
        $id = $this->connection->fetchOne(
            'SELECT LOWER(HEX(id)) FROM medical_warehouse WHERE code = :code',
            ['code' => strtoupper(trim($warehouseCode))]
        );

        if (!is_string($id) || !Uuid::isValid($id)) {
            throw new DomainException(sprintf('Warehouse "%s" no longer exists.', $warehouseCode));
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
            throw new DomainException('A warehouse referenced by an active reservation no longer exists.');
        }

        return $code;
    }
}
