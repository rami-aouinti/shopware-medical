<?php declare(strict_types=1);

namespace MedicalInventory\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1793491200CreateWarehouseTable extends MigrationStep
{
    #[\Override]
    public function getCreationTimestamp(): int
    {
        return 1793491200;
    }

    #[\Override]
    public function update(Connection $connection): void
    {
        $connection->executeStatement(<<<'SQL'
            CREATE TABLE IF NOT EXISTS `medical_warehouse` (
                `id` BINARY(16) NOT NULL,
                `code` VARCHAR(64) NOT NULL,
                `name` VARCHAR(255) NOT NULL,
                `country_code` VARCHAR(2) NOT NULL,
                `is_active` TINYINT(1) NOT NULL DEFAULT 1,
                `created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3) NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uniq.medical_warehouse.code` (`code`)
            ) ENGINE=InnoDB
              DEFAULT CHARSET=utf8mb4
              COLLATE=utf8mb4_unicode_ci
        SQL);
    }
}
