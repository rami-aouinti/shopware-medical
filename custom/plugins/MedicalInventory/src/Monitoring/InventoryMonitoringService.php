<?php declare(strict_types=1);

namespace MedicalInventory\Monitoring;

use Doctrine\DBAL\Connection;

final class InventoryMonitoringService
{
    public function __construct(private readonly Connection $connection)
    {
    }

    /**
     * @return array{warehouses: array{total: int, active: int}, stock: array{rows: int, physical: int, reserved: int, available: int}, alerts: array{open: int}, reservations: array{active: int}, shipments: array{open: int}}
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
                    (SELECT COALESCE(SUM(quantity - reserved_quantity), 0) FROM medical_warehouse_stock) AS stock_available,
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
            'alerts' => ['open' => (int) $row['alerts_open']],
            'reservations' => ['active' => (int) $row['reservations_active']],
            'shipments' => ['open' => (int) $row['shipments_open']],
        ];
    }

    public function renderPrometheusMetrics(): string
    {
        $metrics = $this->collectMetrics();
        $gauges = [
            'medical_inventory_warehouses_total' => ['Total number of medical warehouses', $metrics['warehouses']['total']],
            'medical_inventory_warehouses_active' => ['Number of active medical warehouses', $metrics['warehouses']['active']],
            'medical_inventory_stock_rows' => ['Number of product warehouse stock rows', $metrics['stock']['rows']],
            'medical_inventory_stock_physical_units' => ['Physical stock units across all warehouses', $metrics['stock']['physical']],
            'medical_inventory_stock_reserved_units' => ['Reserved stock units across all warehouses', $metrics['stock']['reserved']],
            'medical_inventory_stock_available_units' => ['Available stock units across all warehouses', $metrics['stock']['available']],
            'medical_inventory_open_alerts' => ['Number of open low stock alerts', $metrics['alerts']['open']],
            'medical_inventory_active_reservations' => ['Number of active stock reservations', $metrics['reservations']['active']],
            'medical_inventory_shipments_shipped' => ['Number of shipments currently in SHIPPED state', $metrics['shipments']['open']],
        ];

        $lines = [];
        foreach ($gauges as $name => [$help, $value]) {
            $lines[] = sprintf('# HELP %s %s', $name, $help);
            $lines[] = sprintf('# TYPE %s gauge', $name);
            $lines[] = sprintf('%s %d', $name, $value);
        }

        return implode("\n", $lines) . "\n";
    }
}
