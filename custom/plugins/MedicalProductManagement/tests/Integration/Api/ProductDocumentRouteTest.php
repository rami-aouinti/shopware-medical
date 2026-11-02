<?php declare(strict_types=1);
namespace MedicalProductManagement\Tests\Integration\Api;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Test\KernelTestBehaviour;
use Symfony\Component\Routing\RouterInterface;
class ProductDocumentRouteTest extends TestCase
{
    use KernelTestBehaviour;
    public function testRoutesHaveExpectedAcl(): void
    {
        $routes=static::getContainer()->get(RouterInterface::class)->getRouteCollection();
        $expected=[
            'api.action.medical_product_document.list'=>['GET',['product:read']],
            'api.action.medical_product_document.create'=>['POST',['product:update']],
            'api.action.medical_product_document.update'=>['PATCH',['product:update']],
            'api.action.medical_product_document.delete'=>['DELETE',['product:delete']],
        ];
        foreach ($expected as $name=>[$method,$acl]) {
            $route=$routes->get($name);
            self::assertNotNull($route,$name);
            self::assertContains($method,$route->getMethods());
            self::assertSame($acl,$route->getDefault('_acl'));
        }
    }
}