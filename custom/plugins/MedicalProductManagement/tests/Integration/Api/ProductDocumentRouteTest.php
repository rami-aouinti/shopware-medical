<?php declare(strict_types=1);

namespace MedicalProductManagement\Tests\Integration\Api;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Test\TestCaseBase\AdminApiTestBehaviour;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\Routing\RouterInterface;

class ProductDocumentRouteTest extends TestCase
{
    use KernelTestBehaviour;
    use AdminApiTestBehaviour;

    public function testActionEndpointRejectsUnauthenticatedRequests(): void
    {
        $browser = $this->createClient(authorized: false);
        $browser->request(
            'GET',
            '/api/_action/medical-product-management/products/' . Uuid::randomHex() . '/documents'
        );

        self::assertSame(401, $browser->getResponse()->getStatusCode());
    }

    public function testListEndpointAllowsProductReadPrivilege(): void
    {
        $browser = $this->createClient(permissions: ['product:read']);
        $browser->jsonRequest(
            'GET',
            '/api/_action/medical-product-management/products/' . Uuid::randomHex() . '/documents'
        );

        self::assertSame(200, $browser->getResponse()->getStatusCode());
    }

    public function testCreateEndpointRequiresProductUpdatePrivilege(): void
    {
        $browser = $this->createClient(permissions: ['product:read']);
        $browser->jsonRequest(
            'POST',
            '/api/_action/medical-product-management/products/' . Uuid::randomHex() . '/documents',
            [
                'title' => 'Technical datasheet',
                'documentType' => 'TECHNICAL_DATASHEET',
                'mediaId' => Uuid::randomHex(),
                'isPrivate' => false,
            ]
        );

        self::assertSame(403, $browser->getResponse()->getStatusCode());
    }

    public function testUpdateEndpointRequiresProductUpdatePrivilege(): void
    {
        $browser = $this->createClient(permissions: ['product:read']);
        $browser->jsonRequest(
            'PATCH',
            '/api/_action/medical-product-management/documents/' . Uuid::randomHex(),
            ['title' => 'Updated title']
        );

        self::assertSame(403, $browser->getResponse()->getStatusCode());
    }

    public function testDeleteEndpointRequiresProductDeletePrivilege(): void
    {
        $browser = $this->createClient(permissions: ['product:read']);
        $browser->request(
            'DELETE',
            '/api/_action/medical-product-management/documents/' . Uuid::randomHex()
        );

        self::assertSame(403, $browser->getResponse()->getStatusCode());
    }

    public function testPrivateDownloadRequiresProductAndMediaReadPrivileges(): void
    {
        $browser = $this->createClient(permissions: ['product:read']);
        $browser->request(
            'GET',
            '/api/_action/medical-product-management/documents/' . Uuid::randomHex() . '/download'
        );

        self::assertSame(403, $browser->getResponse()->getStatusCode());
    }

    public function testRoutesHaveExpectedAcl(): void
    {
        $routes = static::getContainer()->get(RouterInterface::class)->getRouteCollection();
        $expected = [
            'api.action.medical_product_document.list' => ['GET', ['product:read']],
            'api.action.medical_product_document.create' => ['POST', ['product:update']],
            'api.action.medical_product_document.update' => ['PATCH', ['product:update']],
            'api.action.medical_product_document.delete' => ['DELETE', ['product:delete']],
            'api.action.medical_product_document.download_private' => ['GET', ['product:read', 'media:read']],
        ];

        foreach ($expected as $name => [$method, $acl]) {
            $route = $routes->get($name);
            self::assertNotNull($route, $name);
            self::assertContains($method, $route->getMethods());
            self::assertSame($acl, $route->getDefault('_acl'));
            self::assertContains('api', $route->getDefault('_routeScope'));
        }
    }
}
