<?php declare(strict_types=1);

namespace MedicalInventory\Controller;

use Doctrine\DBAL\Connection;
use InvalidArgumentException;
use MedicalInventory\Service\LowStockAlertService;
use MedicalInventory\Service\ShipmentService;
use MedicalInventory\Service\LowStockAlertService;
use MedicalInventory\Service\ShipmentService;
use MedicalInventory\Service\WarehouseService;
use MedicalInventory\Service\WarehouseStockService;
use Shopware\Core\Framework\Context;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route(defaults: ['_routeScope' => ['api']])]
final class MedicalInventoryAdminController
{
    public function __construct(
        private readonly Connection $connection,
        private readonly WarehouseStockService $stockService,
        private readonly WarehouseService $warehouseService,
        private readonly ShipmentService $shipmentService,
        private readonly LowStockAlertService $alertService,
    ) {
    }

    #[Route(
        path: '/api/_action/medical-inventory/dashboard',
        name: 'api.action.medical_inventory.dashboard',
        methods: ['GET']
    )]
    public function dashboard(): JsonResponse
    {
        $warehouses = $this->connection->fetchAllAssociative(
            <<<'SQL'
                SELECT
                    LOWER(HEX(w.id)) AS id,
                    w.code,
                    w.name,
                    w.country_code,
                    w.priority,
                    w.is_active AS active,
                    COUNT(s.id) AS stock_rows,
                    COALESCE(SUM(s.quantity - s.reserved_quantity), 0) AS available_units
                FROM medical_warehouse w
                LEFT JOIN medical_warehouse_stock s ON s.warehouse_id = w.id
                GROUP BY w.id, w.code, w.name, w.country_code, w.priority, w.is_active
                ORDER BY w.priority ASC, w.name ASC
            SQL
        );

        $stock = $this->connection->fetchAllAssociative(
            <<<'SQL'
                SELECT
                    w.code AS warehouse_code,
                    LOWER(HEX(s.product_id)) AS product_id,
                    p.product_number,
                    s.quantity,
                    s.reserved_quantity,
                    s.quantity - s.reserved_quantity AS available_quantity,
                    s.minimum_quantity
                FROM medical_warehouse_stock s
                INNER JOIN medical_warehouse w ON w.id = s.warehouse_id
                INNER JOIN product p
                    ON p.id = s.product_id
                   AND p.version_id = s.product_version_id
                ORDER BY w.priority ASC, p.product_number ASC
                LIMIT 500
            SQL
        );

        $summary = [
            'warehouses' => (int) $this->connection->fetchOne('SELECT COUNT(*) FROM medical_warehouse'),
            'activeWarehouses' => (int) $this->connection->fetchOne(
                'SELECT COUNT(*) FROM medical_warehouse WHERE is_active = 1'
            ),
            'openAlerts' => (int) $this->connection->fetchOne(
                "SELECT COUNT(*) FROM medical_stock_alert WHERE status = 'OPEN'"
            ),
            'activeReservations' => (int) $this->connection->fetchOne(
                "SELECT COUNT(*) FROM medical_stock_reservation WHERE status = 'ACTIVE'"
            ),
            'openShipments' => (int) $this->connection->fetchOne(
                "SELECT COUNT(*) FROM medical_shipment WHERE status = 'SHIPPED'"
            ),
        ];

        $alerts = $this->connection->fetchAllAssociative(
            <<<'SQL'
                SELECT
                    LOWER(HEX(a.id)) AS id,
                    w.code AS warehouse_code,
                    LOWER(HEX(a.product_id)) AS product_id,
                    a.threshold_quantity,
                    a.available_quantity,
                    a.opened_at
                FROM medical_stock_alert a
                INNER JOIN medical_warehouse w ON w.id = a.warehouse_id
                WHERE a.status = 'OPEN'
                ORDER BY a.opened_at ASC
                LIMIT 100
            SQL
        );

        return new JsonResponse([
            'summary' => $summary,
            'warehouses' => $warehouses,
            'stock' => $stock,
            'alerts' => $alerts,
        ]);
    }

    #[Route(
        path: '/api/_action/medical-inventory/warehouses',
        name: 'api.action.medical_inventory.warehouse.create',
        methods: ['POST']
    )]
    public function createWarehouse(Request $request, Context $context): JsonResponse
    {
        $payload = $request->toArray();
        $code = $payload['code'] ?? null;
        $name = $payload['name'] ?? null;
        $countryCode = $payload['countryCode'] ?? null;
        $priority = $payload['priority'] ?? 100;

        if (
            !is_string($code)
            || !is_string($name)
            || !is_string($countryCode)
            || !is_int($priority)
        ) {
            throw new InvalidArgumentException('code, name, countryCode and integer priority are required.');
        }

        $this->warehouseService->create($code, $name, $countryCode, $context, $priority);

        return new JsonResponse(['success' => true], 201);
    }

    #[Route(
        path: '/api/_action/medical-inventory/warehouses/{warehouseCode}',
        name: 'api.action.medical_inventory.warehouse.update',
        methods: ['PATCH']
    )]
    public function updateWarehouse(string $warehouseCode, Request $request, Context $context): JsonResponse
    {
        $payload = $request->toArray();

        if (isset($payload['priority'])) {
            if (!is_int($payload['priority'])) {
                throw new InvalidArgumentException('priority must be an integer.');
            }

            $this->warehouseService->setPriorityByCode($warehouseCode, $payload['priority'], $context);
        }

        if (isset($payload['active'])) {
            if (!is_bool($payload['active'])) {
                throw new InvalidArgumentException('active must be a boolean.');
            }

            $this->warehouseService->setActiveByCode($warehouseCode, $payload['active'], $context);
        }

        return new JsonResponse(['success' => true]);
    }

    #[Route(
        path: '/api/_action/medical-inventory/alerts/scan',
        name: 'api.action.medical_inventory.alerts.scan',
        methods: ['POST']
    )]
    public function scanAlerts(): JsonResponse
    {
        return new JsonResponse([
            'success' => true,
            'openOrUpdated' => $this->alertService->scan(),
        ]);
    }

    #[Route(
        path: '/api/_action/medical-inventory/orders/{orderId}/shipments',
        name: 'api.action.medical_inventory.shipment.create',
        methods: ['POST']
    )]
    public function createShipment(string $orderId, Request $request, Context $context): JsonResponse
    {
        $payload = $request->toArray();
        $warehouseCode = $payload['warehouseCode'] ?? null;
        $items = $payload['items'] ?? null;

        if (!is_string($warehouseCode) || !is_array($items) || $items === []) {
            throw new InvalidArgumentException('warehouseCode and a non-empty items array are required.');
        }

        $validatedItems = [];

        foreach ($items as $item) {
            if (
                !is_array($item)
                || !is_string($item['productId'] ?? null)
                || !is_int($item['quantity'] ?? null)
            ) {
                throw new InvalidArgumentException('Every shipment item requires a productId and integer quantity.');
            }

            $validatedItems[] = [
                'productId' => $item['productId'],
                'quantity' => $item['quantity'],
            ];
        }

        $shipmentId = $this->shipmentService->createShipment(
            $orderId,
            $warehouseCode,
            $validatedItems,
            $context,
            is_string($payload['carrierCode'] ?? null) ? $payload['carrierCode'] : null,
            is_string($payload['trackingNumber'] ?? null) ? $payload['trackingNumber'] : null
        );

        return new JsonResponse(['success' => true, 'shipmentId' => $shipmentId], 201);
    }

    #[Route(
        path: '/api/_action/medical-inventory/shipments/{shipmentId}/delivered',
        name: 'api.action.medical_inventory.shipment.delivered',
        methods: ['PATCH']
    )]
    public function markShipmentDelivered(string $shipmentId, Request $request): JsonResponse
    {
        $payload = $request->toArray();
        $location = is_string($payload['location'] ?? null) ? $payload['location'] : null;
        $this->shipmentService->markDelivered($shipmentId, $location);

        return new JsonResponse(['success' => true]);
    }

    #[Route(
        path: '/api/_action/medical-inventory/warehouses/{warehouseCode}/products/{productId}/minimum',
        name: 'api.action.medical_inventory.minimum_stock',
        methods: ['PATCH']
    )]
    public function updateMinimumStock(
        string $warehouseCode,
        string $productId,
        Request $request,
        Context $context,
    ): JsonResponse {
        $payload = $request->toArray();
        $minimumQuantity = $payload['minimumQuantity'] ?? null;

        if (!is_int($minimumQuantity) || $minimumQuantity < 0) {
            throw new InvalidArgumentException('minimumQuantity must be a non-negative integer.');
        }

        $this->stockService->setMinimumQuantity(
            $warehouseCode,
            $productId,
            $minimumQuantity,
            $context
        );

        return new JsonResponse(['success' => true]);
    }
}
