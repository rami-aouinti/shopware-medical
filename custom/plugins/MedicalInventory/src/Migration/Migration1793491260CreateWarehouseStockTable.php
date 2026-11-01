<?php declare(strict_types=1);

namespace MedicalInventory\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1793491260CreateWarehouseStockTable extends MigrationStep
{
    #[\Override]
    public function getCreationTimestamp(): int
    {
        return 1793491260;
    }

    #[\Override]
    public function update(Connection $connection): void
    {
        $connection->executeStatement(<<<'SQL'
            CREATE TABLE IF NOT EXISTS `medical_warehouse_stock` (
                `id` BINARY(16) NOT NULL,
                `warehouse_id` BINARY(16) NOT NULL,
                `product_id` BINARY(16) NOT NULL,
                `product_version_id` BINARY(16) NOT NULL,
                `quantity` INT UNSIGNED NOT NULL DEFAULT 0,
                `reserved_quantity` INT UNSIGNED NOT NULL DEFAULT 0,
                `created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3) NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uniq.medical_warehouse_stock.warehouse_product`
                    (`warehouse_id`, `product_id`, `product_version_id`),
                KEY `idx.medical_warehouse_stock.product` (`product_id`, `product_version_id`),
                CONSTRAINT `fk.medical_warehouse_stock.warehouse`
                    FOREIGN KEY (`warehouse_id`) REFERENCES `medical_warehouse` (`id`)
                    ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk.medical_warehouse_stock.product`
                    FOREIGN KEY (`product_id`, `product_version_id`)
                    REFERENCES `product` (`id`, `version_id`)
                    ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `chk.medical_warehouse_stock.reserved_quantity`
                    CHECK (`reserved_quantity` <= `quantity`)
            ) ENGINE=InnoDB
              DEFAULT CHARSET=utf8mb4
              COLLATE=utf8mb4_unicode_ci
        SQL);
    }
}
