<?php declare(strict_types=1);

namespace MedicalInventory\Core\Content\Warehouse;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CreatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\UpdatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class WarehouseDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'medical_warehouse';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return WarehouseEntity::class;
    }

    public function getCollectionClass(): string
    {
        return WarehouseCollection::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))
                ->addFlags(new Required(), new PrimaryKey()),

            (new StringField('code', 'code'))
                ->addFlags(new Required()),

            (new StringField('name', 'name'))
                ->addFlags(new Required()),

            (new StringField('country_code', 'countryCode'))
                ->addFlags(new Required()),

            (new BoolField('is_active', 'isActive'))
                ->addFlags(new Required()),

            new CreatedAtField(),

            new UpdatedAtField(),
        ]);
    }
}
