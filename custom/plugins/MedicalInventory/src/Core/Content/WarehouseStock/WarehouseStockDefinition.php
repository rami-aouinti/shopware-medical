<?php declare(strict_types=1);

namespace MedicalInventory\Core\Content\WarehouseStock;

use MedicalInventory\Core\Content\Warehouse\WarehouseDefinition;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CreatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ReferenceVersionField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\UpdatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class WarehouseStockDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'medical_warehouse_stock';

    #[\Override]
    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    #[\Override]
    public function getEntityClass(): string
    {
        return WarehouseStockEntity::class;
    }

    #[\Override]
    public function getCollectionClass(): string
    {
        return WarehouseStockCollection::class;
    }

    #[\Override]
    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))
                ->addFlags(new Required(), new PrimaryKey()),

            (new FkField('warehouse_id', 'warehouseId', WarehouseDefinition::class))
                ->addFlags(new Required()),

            (new FkField('product_id', 'productId', ProductDefinition::class))
                ->addFlags(new Required()),

            (new ReferenceVersionField(ProductDefinition::class))
                ->addFlags(new Required()),

            (new IntField('quantity', 'quantity'))
                ->addFlags(new Required()),

            (new IntField('reserved_quantity', 'reservedQuantity'))
                ->addFlags(new Required()),

            (new IntField('minimum_quantity', 'minimumQuantity'))
                ->addFlags(new Required()),

            new CreatedAtField(),
            new UpdatedAtField(),

            new ManyToOneAssociationField(
                'warehouse',
                'warehouse_id',
                WarehouseDefinition::class,
                'id',
                false
            ),

            new ManyToOneAssociationField(
                'product',
                'product_id',
                ProductDefinition::class,
                'id',
                false
            ),
        ]);
    }
}
