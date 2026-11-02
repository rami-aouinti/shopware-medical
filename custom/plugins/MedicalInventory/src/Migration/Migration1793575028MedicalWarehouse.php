<?php declare(strict_types=1);

namespace MedicalInventory\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Migration\MigrationStep;

/**
 * @internal
 */
#[Package('inventory')]
class Migration1793575028MedicalWarehouse extends MigrationStep
{
    #[\Override]
    public function getCreationTimestamp(): int
    {
        return 1793575028;
    }

    #[\Override]
    public function update(Connection $connection): void
    {
        $schemaManager = $connection->createSchemaManager();

        if (!$schemaManager->tablesExist(['medical_warehouse'])) {
            return;
        }

        $columns = $schemaManager->listTableColumns('medical_warehouse');
        $normalizedColumns = [];

        foreach ($columns as $column) {
            $normalizedColumns[strtolower($column->getName())] = $column;
        }

        // The priority column may already exist after the earlier priority migration.
        if (!isset($normalizedColumns['priority'])) {
            $connection->executeStatement(
                'ALTER TABLE medical_warehouse ADD priority INT NOT NULL DEFAULT 100'
            );
        }

        $connection->executeStatement(
            'ALTER TABLE medical_warehouse'
            . ' CHANGE code code VARCHAR(255) NOT NULL,'
            . ' CHANGE country_code country_code VARCHAR(255) NOT NULL,'
            . ' CHANGE is_active is_active TINYINT DEFAULT 0 NOT NULL'
        );
    }

    #[\Override]
    public function updateDestructive(Connection $connection): void
    {
        // Add destructive update if necessary
    }
}
