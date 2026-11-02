<?php declare(strict_types=1);

namespace MedicalProductManagement\Core\Content\ProductDocument;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @extends EntityCollection<ProductDocumentEntity>
 */
class ProductDocumentCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return ProductDocumentEntity::class;
    }
}