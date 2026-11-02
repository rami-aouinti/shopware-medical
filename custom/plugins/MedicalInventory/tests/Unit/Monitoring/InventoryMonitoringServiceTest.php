<?php declare(strict_types=1);

namespace MedicalInventory\Tests\Unit\Monitoring;

use Doctrine\DBAL\Connection;
use MedicalInventory\Monitoring\InventoryMonitoringService;
use PHPUnit\Framework\TestCase;

final class InventoryMonitoringServiceTest extends TestCase
{
    public function testItReturnsTypedInventoryGauges(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())
            ->method('fetchAssociative')
            ->willReturn([
                'warehouses_total' => '4',
                'warehouses_active' => '3',
                'stock_rows' => '12',
                'stock_physical' => '240',
                'stock_reserved' => '35',
                'stock_available' => '205',
                'alerts_open' => '2',
                'reservations_active' => '5',
                'shipments_open' => '1',
            ]);

        $metrics = (new InventoryMonitoringService($connection))->collectMetrics();

        self::assertSame([
            'warehouses' => ['total' => 4, 'active' => 3],
            'stock' => ['rows' => 12, 'physical' => 240, 'reserved' => 35, 'available' => 205],
            'alerts' => ['open' => 2],
            'reservations' => ['active' => 5],
            'shipments' => ['open' => 1],
        ], $metrics);
    }

    public function testItRendersPrometheusGaugeFormat(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())
            ->method('fetchAssociative')
            ->willReturn([
                'warehouses_total' => '4',
                'warehouses_active' => '3',
                'stock_rows' => '12',
                'stock_physical' => '240',
                'stock_reserved' => '35',
                'stock_available' => '205',
                'alerts_open' => '2',
                'reservations_active' => '5',
                'shipments_open' => '1',
            ]);

        $output = (new InventoryMonitoringService($connection))->renderPrometheusMetrics();

        self::assertStringContainsString('# TYPE medical_inventory_stock_available_units gauge', $output);
        self::assertStringContainsString('medical_inventory_stock_available_units 205', $output);
        self::assertStringContainsString('medical_inventory_open_alerts 2', $output);
    }
}
