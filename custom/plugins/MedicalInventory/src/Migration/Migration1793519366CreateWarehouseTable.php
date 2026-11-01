<?php declare(strict_types=1);

namespace MedicalInventory\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

/**
 * @internal
 */
class Migration1793519366CreateWarehouseTable extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1793519366;
    }

    public function update(Connection $connection): void
    {

    }
}
