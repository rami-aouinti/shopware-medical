<?php declare(strict_types=1);

namespace MedicalInventory\Core\Content\WarehouseStock;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @extends EntityCollection<WarehouseStockEntity>
 */
class WarehouseStockCollection extends EntityCollection
{
    #[\Override]
    #[\Override]
    protected function getExpectedClass(): string
    {
        return WarehouseStockEntity::class;
    }
}
