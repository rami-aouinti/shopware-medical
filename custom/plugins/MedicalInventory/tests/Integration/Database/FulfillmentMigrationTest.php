<?php declare(strict_types=1);

namespace MedicalInventory\Tests\Integration\Database;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use MedicalInventory\Migration\Migration1793496000AddFulfillmentLifecycle;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Uuid\Uuid;

final class FulfillmentMigrationTest extends TestCase
{
    private ?Connection $connection = null;

    #[\Override]
    protected function setUp(): void
    {
        $databaseUrl = getenv('MEDICAL_INVENTORY_TEST_DATABASE_URL');

        if (!is_string($databaseUrl) || $databaseUrl === '') {
            self::markTestSkipped('Set MEDICAL_INVENTORY_TEST_DATABASE_URL to run MariaDB integration tests.');
        }

        $this->connection = DriverManager::getConnection(['url' => $databaseUrl]);

        foreach ([
            'medical_stock_alert',
            'medical_shipment_event',
            'medical_shipment_item',
            'medical_shipment',
            'medical_stock_reservation',
            'medical_order_warehouse_allocation',
            'medical_stock_movement',
            'medical_warehouse_stock',
        ] as $table) {
            $this->connection->executeStatement('DROP TABLE IF EXISTS ' . $table);
        }

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
            'warehouse_id' => Uuid::fromHexToBytes(Uuid::randomHex()),
            'product_id' => Uuid::fromHexToBytes(Uuid::randomHex()),
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

        foreach ([
            'medical_stock_movement',
            'medical_order_warehouse_allocation',
            'medical_stock_reservation',
            'medical_shipment',
            'medical_shipment_item',
            'medical_shipment_event',
            'medical_stock_alert',
        ] as $table) {
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
