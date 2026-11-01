<?php declare(strict_types=1);

namespace MedicalInventory\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Migration\MigrationStep;

/**
 * @internal
 */
#[Package('core')]
class Migration1793575028MedicalWarehouse extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1793575028;
    }

    public function update(Connection $connection): void
    {
        $query = <<<'SQL'
            ALTER TABLE medical_warehouse ADD priority INT NOT NULL, CHANGE `code` code VARCHAR(255) NOT NULL, CHANGE `country_code` country_code VARCHAR(255) NOT NULL, CHANGE `is_active` is_active TINYINT DEFAULT 0 NOT NULL;
SQL;

        $connection->executeStatement($query);
    }

    public function updateDestructive(Connection $connection): void
    {
        // Add destructive update if necessary
    }
}
