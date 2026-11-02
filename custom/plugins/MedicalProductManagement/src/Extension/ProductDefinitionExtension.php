<?php declare(strict_types=1);

namespace MedicalProductManagement\Extension;

use MedicalProductManagement\Core\Content\ProductDocument\ProductDocumentDefinition;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\CascadeDelete;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Extension\EntityExtension;

class ProductDefinitionExtension extends EntityExtension
{
    public function getDefinitionClass(): string
    {
        return ProductDefinition::class;
    }

    public function extendFields(FieldCollection $collection): void
    {
        $collection->add(
            (new OneToManyAssociationField(
                'medicalDocuments',
                ProductDocumentDefinition::class,
                'product_id',
                'id'
            ))->addFlags(new CascadeDelete())
        );
    }
}