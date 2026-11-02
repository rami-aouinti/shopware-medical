<?php declare(strict_types=1);
namespace MedicalProductManagement\Migration;
use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Migration\MigrationStep;
#[Package('core')]
class Migration1793600100AddPrivateFlagToMedicalProductDocument extends MigrationStep
{
    public function getCreationTimestamp(): int { return 1793600100; }
    public function update(Connection $connection): void
    {
        $connection->executeStatement('ALTER TABLE medical_product_document ADD COLUMN is_private TINYINT(1) NOT NULL DEFAULT 0');
    }
    public function updateDestructive(Connection $connection): void {}
}