<?php declare(strict_types=1);

namespace MedicalInventory\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1793496000AddFulfillmentLifecycle extends MigrationStep
{
    #[\Override]
    public function getCreationTimestamp(): int
    {
        return 1793496000;
    }

    #[\Override]
    public function update(Connection $connection): void
    {
        $schemaManager = $connection->createSchemaManager();

        if (!$schemaManager->tablesExist(['medical_warehouse_stock'])) {
            return;
        }

        $columns = array_change_key_case(
            $schemaManager->listTableColumns('medical_warehouse_stock'),
            CASE_LOWER
        );

        if (!isset($columns['minimum_quantity'])) {
            $connection->executeStatement(
                'ALTER TABLE medical_warehouse_stock ADD minimum_quantity INT UNSIGNED NOT NULL DEFAULT 0'
            );
        }

        $connection->executeStatement(<<<'SQL'
            CREATE TABLE IF NOT EXISTS medical_stock_movement (
                id BINARY(16) NOT NULL,
                warehouse_id BINARY(16) NOT NULL,
                product_id BINARY(16) NOT NULL,
                product_version_id BINARY(16) NOT NULL,
                movement_type VARCHAR(32) NOT NULL,
                quantity_delta INT NOT NULL DEFAULT 0,
                reserved_quantity_delta INT NOT NULL DEFAULT 0,
                reference_type VARCHAR(64) DEFAULT NULL,
                reference_id VARCHAR(64) DEFAULT NULL,
                reason VARCHAR(255) DEFAULT NULL,
                created_at DATETIME(3) NOT NULL,
                PRIMARY KEY (id),
                INDEX idx_medical_movement_stock (warehouse_id, product_id, product_version_id, created_at),
                INDEX idx_medical_movement_reference (reference_type, reference_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        SQL);

        $connection->executeStatement(<<<'SQL'
            CREATE TABLE IF NOT EXISTS medical_order_warehouse_allocation (
                id BINARY(16) NOT NULL,
                order_id BINARY(16) NOT NULL,
                order_version_id BINARY(16) NOT NULL,
                product_id BINARY(16) NOT NULL,
                product_version_id BINARY(16) NOT NULL,
                warehouse_id BINARY(16) NOT NULL,
                quantity INT UNSIGNED NOT NULL,
                shipped_quantity INT UNSIGNED NOT NULL DEFAULT 0,
                status VARCHAR(24) NOT NULL,
                created_at DATETIME(3) NOT NULL,
                updated_at DATETIME(3) DEFAULT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY uniq_medical_order_allocation (order_id, order_version_id, product_id, product_version_id, warehouse_id),
                INDEX idx_medical_allocation_order (order_id, order_version_id),
                INDEX idx_medical_allocation_warehouse (warehouse_id, status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        SQL);

        $connection->executeStatement(<<<'SQL'
            CREATE TABLE IF NOT EXISTS medical_stock_reservation (
                id BINARY(16) NOT NULL,
                allocation_id BINARY(16) NOT NULL,
                order_id BINARY(16) NOT NULL,
                order_version_id BINARY(16) NOT NULL,
                warehouse_id BINARY(16) NOT NULL,
                product_id BINARY(16) NOT NULL,
                product_version_id BINARY(16) NOT NULL,
                quantity INT UNSIGNED NOT NULL,
                consumed_quantity INT UNSIGNED NOT NULL DEFAULT 0,
                status VARCHAR(24) NOT NULL,
                expires_at DATETIME(3) DEFAULT NULL,
                created_at DATETIME(3) NOT NULL,
                updated_at DATETIME(3) DEFAULT NULL,
                released_at DATETIME(3) DEFAULT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY uniq_medical_reservation_allocation (allocation_id),
                INDEX idx_medical_reservation_order (order_id, order_version_id, status),
                INDEX idx_medical_reservation_expiry (status, expires_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        SQL);

        $connection->executeStatement(<<<'SQL'
            CREATE TABLE IF NOT EXISTS medical_shipment (
                id BINARY(16) NOT NULL,
                order_id BINARY(16) NOT NULL,
                order_version_id BINARY(16) NOT NULL,
                warehouse_id BINARY(16) NOT NULL,
                carrier_code VARCHAR(64) DEFAULT NULL,
                tracking_number VARCHAR(128) DEFAULT NULL,
                status VARCHAR(24) NOT NULL,
                shipped_at DATETIME(3) DEFAULT NULL,
                delivered_at DATETIME(3) DEFAULT NULL,
                created_at DATETIME(3) NOT NULL,
                updated_at DATETIME(3) DEFAULT NULL,
                PRIMARY KEY (id),
                INDEX idx_medical_shipment_order (order_id, order_version_id),
                INDEX idx_medical_shipment_warehouse (warehouse_id, status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        SQL);

        $connection->executeStatement(<<<'SQL'
            CREATE TABLE IF NOT EXISTS medical_shipment_item (
                id BINARY(16) NOT NULL,
                shipment_id BINARY(16) NOT NULL,
                allocation_id BINARY(16) NOT NULL,
                product_id BINARY(16) NOT NULL,
                quantity INT UNSIGNED NOT NULL,
                created_at DATETIME(3) NOT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY uniq_medical_shipment_allocation (shipment_id, allocation_id),
                INDEX idx_medical_shipment_item_allocation (allocation_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        SQL);

        $connection->executeStatement(<<<'SQL'
            CREATE TABLE IF NOT EXISTS medical_shipment_event (
                id BINARY(16) NOT NULL,
                shipment_id BINARY(16) NOT NULL,
                event_code VARCHAR(64) NOT NULL,
                description VARCHAR(255) NOT NULL,
                location VARCHAR(255) DEFAULT NULL,
                event_at DATETIME(3) NOT NULL,
                created_at DATETIME(3) NOT NULL,
                PRIMARY KEY (id),
                INDEX idx_medical_shipment_event (shipment_id, event_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        SQL);

        $connection->executeStatement(<<<'SQL'
            CREATE TABLE IF NOT EXISTS medical_stock_alert (
                id BINARY(16) NOT NULL,
                warehouse_id BINARY(16) NOT NULL,
                product_id BINARY(16) NOT NULL,
                product_version_id BINARY(16) NOT NULL,
                threshold_quantity INT UNSIGNED NOT NULL,
                available_quantity INT UNSIGNED NOT NULL,
                status VARCHAR(24) NOT NULL,
                opened_at DATETIME(3) NOT NULL,
                resolved_at DATETIME(3) DEFAULT NULL,
                PRIMARY KEY (id),
                INDEX idx_medical_stock_alert_status (status, opened_at),
                INDEX idx_medical_stock_alert_product (warehouse_id, product_id, product_version_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        SQL);
    }
}
