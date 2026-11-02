<?php declare(strict_types=1);
namespace MedicalProductManagement\Tests\Integration\Dal;
use MedicalProductManagement\Core\Content\ProductDocument\ProductDocumentDefinition;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\Tax\TaxEntity;
use Shopware\Core\Framework\Validation\WriteConstraintViolationException;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Doctrine\DBAL\Connection;
class ProductDocumentDefinitionTest extends TestCase
{
    use KernelTestBehaviour;

    public function testInvalidMedicalCustomFieldsAreRejectedDuringDalWrite(): void
    {
        $context = Context::createDefaultContext();
        $tax = static::getContainer()
            ->get('tax.repository')
            ->search(new Criteria(), $context)
            ->first();

        self::assertInstanceOf(TaxEntity::class, $tax);

        $this->expectException(WriteConstraintViolationException::class);

        static::getContainer()->get('product.repository')->create([
            [
                'id' => Uuid::randomHex(),
                'productNumber' => Uuid::randomHex(),
                'name' => 'Invalid medical product',
                'stock' => 10,
                'active' => false,
                'tax' => ['id' => $tax->getId()],
                'price' => [[
                    'currencyId' => Defaults::CURRENCY,
                    'gross' => 12.0,
                    'net' => 10.0,
                    'linked' => false,
                ]],
                'customFields' => [
                    'package_quantity' => 0,
                    'minimum_order_quantity' => -1,
                ],
            ],
        ], $context);
    }

    public function testDefinitionIsRegisteredWithPrivacyAndAssociations(): void
    {
        $registry=static::getContainer()->get(DefinitionInstanceRegistry::class);
        $definition=$registry->getByEntityName(ProductDocumentDefinition::ENTITY_NAME);
        self::assertInstanceOf(ProductDocumentDefinition::class, $definition);
        self::assertNotNull(static::getContainer()->get('medical_product_document.repository'));
        self::assertTrue(
            static::getContainer()
                ->get(Connection::class)
                ->createSchemaManager()
                ->tablesExist(['medical_product_document'])
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

    public function testSchemaAllowsMultipleDocumentsPerProductAndRestrictsMediaDeletion(): void
    {
        $schemaManager = static::getContainer()
            ->get(Connection::class)
            ->createSchemaManager();

        $indexes = $schemaManager->listTableIndexes('medical_product_document');
        $uniqueNonPrimaryIndexes = array_filter(
            $indexes,
            static fn ($index): bool => $index->isUnique() && strtolower($index->getName()) !== 'primary'
        );

        self::assertCount(
            0,
            $uniqueNonPrimaryIndexes,
            'A product must be able to reference multiple medical documents.'
        );

        $mediaForeignKey = null;
        foreach ($schemaManager->listTableForeignKeys('medical_product_document') as $foreignKey) {
            if ($foreignKey->getForeignTableName() === 'media') {
                $mediaForeignKey = $foreignKey;
                break;
            }
        }

        self::assertNotNull($mediaForeignKey, 'The document must reference an existing Shopware media row.');
        self::assertSame('RESTRICT', strtoupper((string) $mediaForeignKey->onDelete()));
    }
}