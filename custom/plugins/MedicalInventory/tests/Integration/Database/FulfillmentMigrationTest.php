<?php declare(strict_types=1);

namespace MedicalInventory\Tests\Integration\Database;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use MedicalInventory\Migration\Migration1793496000AddFulfillmentLifecycle;
use MedicalInventory\Service\LowStockAlertService;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Uuid\Uuid;

final class FulfillmentMigrationTest extends TestCase
{
    private ?Connection $connection = null;

    private string $warehouseId;

    private string $productId;

    #[\Override]
    protected function setUp(): void
    {
        $databaseUrl = getenv('MEDICAL_INVENTORY_TEST_DATABASE_URL');

        if (!is_string($databaseUrl) || $databaseUrl === '') {
            self::markTestSkipped('Set MEDICAL_INVENTORY_TEST_DATABASE_URL to run MariaDB integration tests.');
        }

        $parts = parse_url($databaseUrl);

        if (!is_array($parts)) {
            throw new RuntimeException('MEDICAL_INVENTORY_TEST_DATABASE_URL is invalid.');
        }

        $this->connection = DriverManager::getConnection([
            'driver' => 'pdo_mysql',
            'host' => is_string($parts['host'] ?? null) ? $parts['host'] : '127.0.0.1',
            'port' => is_int($parts['port'] ?? null) ? $parts['port'] : 3306,
            'user' => is_string($parts['user'] ?? null) ? $parts['user'] : 'root',
            'password' => is_string($parts['pass'] ?? null) ? $parts['pass'] : '',
            'dbname' => ltrim(is_string($parts['path'] ?? null) ? $parts['path'] : '', '/'),
            'serverVersion' => 'mariadb-11.4.0',
        ]);

        foreach (
            [
                'medical_stock_alert',
                'medical_shipment_event',
                'medical_shipment_item',
                'medical_shipment',
                'medical_stock_reservation',
                'medical_order_warehouse_allocation',
                'medical_stock_movement',
                'medical_warehouse_stock',
                'medical_warehouse',
            ] as $table
        ) {
            $this->connection->executeStatement('DROP TABLE IF EXISTS ' . $table);
        }

        $this->warehouseId = Uuid::randomHex();
        $this->productId = Uuid::randomHex();

        $this->connection->executeStatement(<<<'SQL'
            CREATE TABLE medical_warehouse (
                id BINARY(16) NOT NULL,
                code VARCHAR(64) NOT NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                PRIMARY KEY (id),
                UNIQUE KEY uniq_medical_warehouse_code (code)
            ) ENGINE=InnoDB
        SQL);

        $this->connection->insert('medical_warehouse', [
            'id' => Uuid::fromHexToBytes($this->warehouseId),
            'code' => 'TEST-WH',
            'is_active' => 1,
        ]);

        $this->connection->executeStatement(<<<'SQL'
            CREATE TABLE medical_warehouse_stock (
                id BINARY(16) NOT NULL,
                warehouse_id BINARY(16) NOT NULL,
                product_id BINARY(16) NOT NULL,
                product_version_id BINARY(16) NOT NULL,
                quantity INT UNSIGNED NOT NULL,
                reserved_quantity INT UNSIGNED NOT NULL,
                PRIMARY KEY (id)
            ) ENGINE=InnoDB
        SQL);

        $this->connection->insert('medical_warehouse_stock', [
            'id' => Uuid::fromHexToBytes(Uuid::randomHex()),
            'warehouse_id' => Uuid::fromHexToBytes($this->warehouseId),
            'product_id' => Uuid::fromHexToBytes($this->productId),
            'product_version_id' => Uuid::fromHexToBytes(Defaults::LIVE_VERSION),
            'quantity' => 27,
            'reserved_quantity' => 4,
        ]);
    }

    #[\Override]
    protected function tearDown(): void
    {
        $this->connection?->close();
    }

    public function testMigrationAddsFulfillmentTablesAndPreservesExistingStock(): void
    {
        $this->migration()->update($this->connection());

        $stock = $this->connection()->fetchAssociative(
            'SELECT quantity, reserved_quantity, minimum_quantity FROM medical_warehouse_stock'
        );

        self::assertIsArray($stock);
        self::assertSame(27, (int) $stock['quantity']);
        self::assertSame(4, (int) $stock['reserved_quantity']);
        self::assertSame(0, (int) $stock['minimum_quantity']);

        foreach (
            [
                'medical_stock_movement',
                'medical_order_warehouse_allocation',
                'medical_stock_reservation',
                'medical_shipment',
                'medical_shipment_item',
                'medical_shipment_event',
                'medical_stock_alert',
            ] as $table
        ) {
            self::assertTrue($this->connection()->createSchemaManager()->tablesExist([$table]), $table);
        }
    }

    public function testMigrationIsIdempotent(): void
    {
        $migration = $this->migration();
        $migration->update($this->connection());
        $migration->update($this->connection());

        self::assertSame(1, (int) $this->connection()->fetchOne(
            'SELECT COUNT(*) FROM medical_warehouse_stock'
        ));
    }

    public function testMovementAuditRowsParticipateInTheStockTransaction(): void
    {
        $this->migration()->update($this->connection());

        try {
            $this->connection()->transactional(function (): void {
                $this->connection()->insert('medical_stock_movement', [
                    'id' => Uuid::fromHexToBytes(Uuid::randomHex()),
                    'warehouse_id' => Uuid::fromHexToBytes(Uuid::randomHex()),
                    'product_id' => Uuid::fromHexToBytes(Uuid::randomHex()),
                    'product_version_id' => Uuid::fromHexToBytes(Defaults::LIVE_VERSION),
                    'movement_type' => 'INBOUND',
                    'quantity_delta' => 10,
                    'reserved_quantity_delta' => 0,
                    'reason' => 'Integration transaction rollback test',
                    'created_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s.v'),
                ]);

                throw new RuntimeException('Force rollback');
            });
        } catch (RuntimeException $exception) {
            self::assertSame('Force rollback', $exception->getMessage());
        }

        self::assertSame(0, (int) $this->connection()->fetchOne('SELECT COUNT(*) FROM medical_stock_movement'));
    }

    public function testLowStockAlertsAreOpenedDeduplicatedAndResolved(): void
    {
        $this->migration()->update($this->connection());
        $this->connection()->executeStatement(
            'UPDATE medical_warehouse_stock SET minimum_quantity = 5, quantity = 8, reserved_quantity = 4'
        );

        $alertService = new LowStockAlertService($this->connection());

        self::assertSame(1, $alertService->scan());
        $alertService->scan();

        self::assertCount(1, $alertService->getOpenAlerts());

        $this->connection()->executeStatement(
            'UPDATE medical_warehouse_stock SET quantity = 20, reserved_quantity = 0'
        );

        self::assertSame(0, $alertService->scan());
        self::assertCount(0, $alertService->getOpenAlerts());
        self::assertSame('RESOLVED', $this->connection()->fetchOne(
            'SELECT status FROM medical_stock_alert'
        ));
    }

    public function testOrderAllocationHasAnIdempotencyUniqueConstraint(): void
    {
        $this->migration()->update($this->connection());
        $allocation = [
            'id' => Uuid::fromHexToBytes(Uuid::randomHex()),
            'order_id' => Uuid::fromHexToBytes(Uuid::randomHex()),
            'order_version_id' => Uuid::fromHexToBytes(Defaults::LIVE_VERSION),
            'product_id' => Uuid::fromHexToBytes(Uuid::randomHex()),
            'product_version_id' => Uuid::fromHexToBytes(Defaults::LIVE_VERSION),
            'warehouse_id' => Uuid::fromHexToBytes(Uuid::randomHex()),
            'quantity' => 5,
            'shipped_quantity' => 0,
            'status' => 'RESERVED',
            'created_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s.v'),
        ];

        $this->connection()->insert('medical_order_warehouse_allocation', $allocation);

        $duplicate = $allocation;
        $duplicate['id'] = Uuid::fromHexToBytes(Uuid::randomHex());

        $this->expectException(UniqueConstraintViolationException::class);
        $this->connection()->insert('medical_order_warehouse_allocation', $duplicate);
    }

    private function migration(): Migration1793496000AddFulfillmentLifecycle
    {
        return new Migration1793496000AddFulfillmentLifecycle();
    }

    private function connection(): Connection
    {
        if (!$this->connection instanceof Connection) {
            throw new RuntimeException('MariaDB integration connection was not initialized.');
        }

        return $this->connection;
    }
}
