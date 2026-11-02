<?php declare(strict_types=1);

namespace MedicalProductManagement\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Migration\MigrationStep;

#[Package('inventory')]
class Migration1793600000CreateMedicalProductDocument extends MigrationStep
{
    #[\Override]
    public function getCreationTimestamp(): int
    {
        return 1793600000;
    }

    #[\Override]
    public function update(Connection $connection): void
    {
        $connection->executeStatement(<<<'SQL'
            CREATE TABLE IF NOT EXISTS `medical_product_document` (
                `id` BINARY(16) NOT NULL,
                `product_id` BINARY(16) NOT NULL,
                `product_version_id` BINARY(16) NOT NULL,
                `media_id` BINARY(16) NOT NULL,
                `document_type` VARCHAR(100) NOT NULL,
                `title` VARCHAR(255) NOT NULL,
                `created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3) NULL,
                PRIMARY KEY (`id`),
                KEY `idx.medical_product_document.product` (`product_id`, `product_version_id`),
                KEY `idx.medical_product_document.media` (`media_id`),
                CONSTRAINT `fk.medical_product_document.product`
                    FOREIGN KEY (`product_id`, `product_version_id`)
                    REFERENCES `product` (`id`, `version_id`)
                    ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk.medical_product_document.media`
                    FOREIGN KEY (`media_id`) REFERENCES `media` (`id`)
                    ON DELETE RESTRICT ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL
        );
    }

    #[\Override]
    public function updateDestructive(Connection $connection): void
    {
    }
}