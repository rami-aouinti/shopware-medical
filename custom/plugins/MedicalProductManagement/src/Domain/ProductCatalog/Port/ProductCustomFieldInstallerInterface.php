<?php declare(strict_types=1);

namespace MedicalProductManagement\Domain\ProductCatalog\Port;

/** Output port for installing medical product catalogue custom fields. */
interface ProductCustomFieldInstallerInterface
{
    public function install(): void;
}
