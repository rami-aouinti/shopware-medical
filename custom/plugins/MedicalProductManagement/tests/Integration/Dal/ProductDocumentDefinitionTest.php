<?php declare(strict_types=1);
namespace MedicalProductManagement\Tests\Integration\Dal;
use MedicalProductManagement\Core\Content\ProductDocument\ProductDocumentDefinition;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Doctrine\DBAL\Connection;
class ProductDocumentDefinitionTest extends TestCase
{
    use KernelTestBehaviour;
    public function testDefinitionIsRegisteredWithPrivacyAndAssociations(): void
    {
        $registry=static::getContainer()->get(DefinitionInstanceRegistry::class);
        $definition=$registry->getByEntityName(ProductDocumentDefinition::ENTITY_NAME);
        self::assertInstanceOf(ProductDocumentDefinition::class, $definition);
        self::assertNotNull(static::getContainer()->get('medical_product_document.repository'));
        self::assertTrue(
            static::getContainer()->get(Connection::class)->createSchemaManager()->tablesExist(['medical_product_document'])
        );

        $productDefinition = $registry->getByEntityName(ProductDefinition::ENTITY_NAME);
        self::assertInstanceOf(
            OneToManyAssociationField::class,
            $productDefinition->getFields()->get('medicalDocuments')
        );
        foreach (['productId','productVersionId','mediaId','isPrivate','product','media'] as $field) {
            self::assertNotNull($definition->getFields()->get($field),sprintf('Missing DAL field %s',$field));
        }
    }
}