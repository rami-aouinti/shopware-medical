<?php declare(strict_types=1);

namespace MedicalInventory\Core\Content\Warehouse;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @extends EntityCollection<WarehouseEntity>
 */
class WarehouseCollection extends EntityCollection
{
    #[\Override]
    protected function getExpectedClass(): string
    {
        return WarehouseEntity::class;
    }
}
