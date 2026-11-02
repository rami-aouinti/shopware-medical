<?php declare(strict_types=1);

namespace MedicalProductManagement\Tests\Unit\Service;

use MedicalProductManagement\Service\ProductCustomFieldInstaller;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;

class ProductCustomFieldInstallerTest extends TestCase
{
    public function testItCreatesTheProductFieldSetIdempotently(): void
    {
        $repository = $this->createMock(EntityRepository::class);
        $repository->expects(self::once())
            ->method('upsert')
            ->with(
                self::callback(static function (array $payload): bool {
                    $set = $payload[0] ?? [];

                    return ($set['name'] ?? null) === 'medical_product_management'
                        && ($set['relations'][0]['entityName'] ?? null) === 'product'
                        && count($set['customFields'] ?? []) === 7
                        && isset($set['customFields'][0]['name'])
                        && $set['customFields'][0]['name'] === 'medical_reference'
                        && $set['customFields'][6]['name'] === 'requires_special_handling';
                }),
                self::isInstanceOf(Context::class)
            );

        $installer = new ProductCustomFieldInstaller($repository);
        $installer->install(Context::createDefaultContext());
    }
}