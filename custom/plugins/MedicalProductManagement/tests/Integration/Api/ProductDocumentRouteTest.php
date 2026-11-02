<?php declare(strict_types=1);
namespace MedicalProductManagement\Tests\Integration\Api;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Shopware\Core\Framework\Uuid\Uuid;
class ProductDocumentRouteTest extends TestCase
{
    use KernelTestBehaviour;

    public function testActionEndpointRejectsUnauthenticatedRequests(): void
    {
        $browser = new KernelBrowser(static::getKernel());
        $browser->request(
            'GET',
            '/api/_action/medical-product-management/products/' . Uuid::randomHex() . '/documents'
        );

        self::assertContains($browser->getResponse()->getStatusCode(), [401, 403]);
    }

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