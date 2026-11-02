<?php declare(strict_types=1);

namespace MedicalProductManagement\Application\ProductCatalog\Install;

use MedicalProductManagement\Domain\ProductCatalog\Port\ProductCustomFieldInstallerInterface;

/** Application use case for installing the medical product catalogue fields. */
final class InstallProductCustomFieldsHandler
{
    public function __construct(
        private readonly ProductCustomFieldInstallerInterface $installer
    ) {
    }

    public function handle(): void
    {
        $this->installer->install();
    }
}
