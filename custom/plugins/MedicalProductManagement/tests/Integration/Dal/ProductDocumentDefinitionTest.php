<?php declare(strict_types=1);
namespace MedicalProductManagement\Tests\Integration\Dal;
use MedicalProductManagement\Core\Content\ProductDocument\ProductDocumentDefinition;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Test\KernelTestBehaviour;
use Shopware\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
class ProductDocumentDefinitionTest extends TestCase
{
    use KernelTestBehaviour;
    public function testDefinitionIsRegisteredWithPrivacyAndAssociations(): void
    {
        $registry=static::getContainer()->get(DefinitionInstanceRegistry::class);
        $definition=$registry->getByEntityName(ProductDocumentDefinition::ENTITY_NAME);
        self::assertInstanceOf(ProductDocumentDefinition::class,$definition);
        foreach (['productId','productVersionId','mediaId','isPrivate','product','media'] as $field) {
            self::assertNotNull($definition->getFields()->get($field),sprintf('Missing DAL field %s',$field));
        }
    }
}