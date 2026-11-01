<?php declare(strict_types=1);

namespace MedicalInventory\Core\Content\Warehouse;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\CascadeDelete;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class WarehouseDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'medical_warehouse';

    #[\Override]
    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    #[\Override]
    public function getEntityClass(): string
    {
        return WarehouseEntity::class;
    }

    #[\Override]
    public function getCollectionClass(): string
    {
        return WarehouseCollection::class;
    }

    #[\Override]
    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))
                ->addFlags(new Required(), new PrimaryKey()),

            (new StringField('code', 'code'))
                ->addFlags(new Required()),

            (new StringField('name', 'name'))
                ->addFlags(new Required()),

            (new IntField('priority', 'priority'))
                ->addFlags(new Required()),

            (new StringField('country_code', 'countryCode'))
                ->addFlags(new Required()),

            (new BoolField('is_active', 'isActive'))
                ->addFlags(new Required()),

            (new OneToManyAssociationField(
                'stocks',
                'medical_warehouse_stock',
                'warehouse_id',
                'id'
            ))->addFlags(new CascadeDelete()),
        ]);
    }
}
