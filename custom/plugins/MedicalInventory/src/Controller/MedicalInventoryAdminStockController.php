<?php declare(strict_types=1);

namespace MedicalInventory\Controller;

use InvalidArgumentException;
use MedicalInventory\Service\WarehouseStockService;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\ContainsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route(defaults: ['_routeScope' => ['api']])]
final class MedicalInventoryAdminStockController
{
    public function __construct(
        private readonly EntityRepository $productRepository,
        private readonly WarehouseStockService $stockService,
    ) {
    }

    #[Route(
        path: '/api/_action/medical-inventory/products',
        name: 'api.action.medical_inventory.products.search',
        methods: ['GET'],
        defaults: ['_acl' => ['product:read']]
    )]
    public function searchProducts(Request $request, Context $context): JsonResponse
    {
        $term = trim((string) $request->query->get('term', ''));

        if (mb_strlen($term) < 2) {
            return new JsonResponse(['data' => []]);
        }

        $criteria = new Criteria();
        $criteria->addFilter(new MultiFilter(MultiFilter::CONNECTION_OR, [
            new ContainsFilter('productNumber', $term),
            new ContainsFilter('name', $term),
        ]));
        $criteria->addSorting(new FieldSorting('productNumber'));
        $criteria->setLimit(20);

        $result = $this->productRepository->search($criteria, $context);
        $products = [];

        foreach ($result->getEntities() as $product) {
            if (!$product instanceof ProductEntity) {
                continue;
            }

            $products[] = [
                'id' => $product->getUniqueIdentifier(),
                'productNumber' => $product->getProductNumber(),
                'name' => (string) ($product->getTranslated()['name'] ?? ''),
            ];
        }

        return new JsonResponse(['data' => $products]);
    }

    #[Route(
        path: '/api/_action/medical-inventory/warehouses/{warehouseCode}/stock',
        name: 'api.action.medical_inventory.warehouse.stock.adjust',
        methods: ['POST'],
        defaults: ['_acl' => ['product:update']]
    )]
    public function adjustStock(
        string $warehouseCode,
        Request $request,
        Context $context,
    ): JsonResponse {
        $payload = $request->toArray();
        $productId = $payload['productId'] ?? null;
        $operation = $payload['operation'] ?? null;
        $quantity = $payload['quantity'] ?? null;

        if (!is_string($productId) || !Uuid::isValid($productId)) {
            throw new InvalidArgumentException('productId must be a valid product UUID.');
        }

        if (!is_string($operation) || !in_array($operation, ['set', 'add', 'remove'], true)) {
            throw new InvalidArgumentException('operation must be set, add or remove.');
        }

        if (!is_int($quantity) || $quantity < 0 || $quantity > 4294967295) {
            throw new InvalidArgumentException('quantity must be an integer between 0 and 4294967295.');
        }

        if ($operation !== 'set' && $quantity === 0) {
            throw new InvalidArgumentException('add and remove operations require a positive quantity.');
        }

        $context->scope(
            Context::SYSTEM_SCOPE,
            function (Context $systemContext) use ($warehouseCode, $productId, $operation, $quantity): void {
                match ($operation) {
                    'set' => $this->stockService->setStock($warehouseCode, $productId, $quantity, $systemContext),
                    'add' => $this->stockService->addStock($warehouseCode, $productId, $quantity, $systemContext),
                    'remove' => $this->stockService->removeStock($warehouseCode, $productId, $quantity, $systemContext),
                };
            }
        );

        return new JsonResponse(['success' => true]);
    }
}
