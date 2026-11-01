<?php declare(strict_types=1);

namespace MedicalInventory\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1793491320AddWarehousePriority extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1793491320;
    }

    public function update(Connection $connection): void
    {
        $schemaManager = $connection->createSchemaManager();

        if (!$schemaManager->tablesExist(['medical_warehouse'])) {
            return;
        }

        $columns = array_change_key_case(
            $schemaManager->listTableColumns('medical_warehouse'),
            CASE_LOWER
        );

        if (isset($columns['priority'])) {
            return;
        }

        $connection->executeStatement(<<<'SQL'
            ALTER TABLE `medical_warehouse`
            ADD COLUMN `priority` SMALLINT UNSIGNED NOT NULL DEFAULT 100 AFTER `name`
        SQL);
    }
}
