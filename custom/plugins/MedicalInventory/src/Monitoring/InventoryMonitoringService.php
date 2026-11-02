<?php declare(strict_types=1);

namespace MedicalInventory\Monitoring;

use Doctrine\DBAL\Connection;

final class InventoryMonitoringService
{
    public function __construct(private readonly Connection $connection)
    {
    }

    /**
     * @return array{
     *     warehouses: array{total: int, active: int},
     *     stock: array{rows: int, physical: int, reserved: int, available: int},
     *     alerts: array{open: int},
     *     reservations: array{active: int},
     *     shipments: array{open: int}
     * }
     */
    public function collectMetrics(): array
    {
        $row = $this->connection->fetchAssociative(
            <<<'SQL'
                SELECT
                    (SELECT COUNT(*) FROM medical_warehouse) AS warehouses_total,
                    (SELECT COUNT(*) FROM medical_warehouse WHERE is_active = 1) AS warehouses_active,
                    (SELECT COUNT(*) FROM medical_warehouse_stock) AS stock_rows,
                    (SELECT COALESCE(SUM(quantity), 0) FROM medical_warehouse_stock) AS stock_physical,
                    (SELECT COALESCE(SUM(reserved_quantity), 0) FROM medical_warehouse_stock) AS stock_reserved,
                    (SELECT COALESCE(SUM(quantity - reserved_quantity), 0)
                        FROM medical_warehouse_stock) AS stock_available,
                    (SELECT COUNT(*) FROM medical_stock_alert WHERE status = 'OPEN') AS alerts_open,
                    (SELECT COUNT(*) FROM medical_stock_reservation WHERE status = 'ACTIVE') AS reservations_active,
                    (SELECT COUNT(*) FROM medical_shipment WHERE status = 'SHIPPED') AS shipments_open
            SQL
        );

        if ($row === false) {
            throw new \RuntimeException('Inventory monitoring metrics could not be collected.');
        }

        return [
            'warehouses' => [
                'total' => (int) $row['warehouses_total'],
                'active' => (int) $row['warehouses_active'],
            ],
            'stock' => [
                'rows' => (int) $row['stock_rows'],
                'physical' => (int) $row['stock_physical'],
                'reserved' => (int) $row['stock_reserved'],
                'available' => (int) $row['stock_available'],
            ],
            'alerts' => [
                'open' => (int) $row['alerts_open'],
            ],
            'reservations' => ['active' => (int) $row['reservations_active']],
            'shipments' => ['open' => (int) $row['shipments_open']],
        ];
    }
}
