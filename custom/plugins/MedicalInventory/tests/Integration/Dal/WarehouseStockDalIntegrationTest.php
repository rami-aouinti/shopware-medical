<?php declare(strict_types=1);

namespace MedicalInventory\Tests\Integration\Dal;

use Doctrine\DBAL\Connection;
use MedicalInventory\Core\Content\Warehouse\WarehouseEntity;
use MedicalInventory\Core\Content\WarehouseStock\WarehouseStockEntity;
use MedicalInventory\Decorator\MedicalInventoryOrderPersister;
use MedicalInventory\Service\WarehouseAllocationService;
use MedicalInventory\Service\WarehouseStockService;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\Order\OrderPersister;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Content\Test\Product\ProductBuilder;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Test\Stub\Framework\IdsCollection;

final class WarehouseStockDalIntegrationTest extends TestCase
{
    use IntegrationTestBehaviour;

    public function testWarehouseEntityCanBeCreatedAndReadThroughTheDal(): void
    {
        $container = static::getContainer();
        /** @var EntityRepository $repository */
        $repository = $container->get('medical_warehouse.repository');
        $context = Context::createDefaultContext();
        $warehouseId = Uuid::randomHex();
        $code = 'DAL-' . strtoupper(substr($warehouseId, 0, 8));

        $repository->create([[
            'id' => $warehouseId,
            'code' => $code,
            'name' => 'DAL integration warehouse',
            'countryCode' => 'DE',
            'priority' => 7,
            'isActive' => true,
        ]], $context);

        $warehouse = $repository->search(new Criteria([$warehouseId]), $context)->first();

        self::assertInstanceOf(WarehouseEntity::class, $warehouse);
        self::assertSame($code, $warehouse->getCode());
        self::assertSame(7, $warehouse->getPriority());
        self::assertTrue($warehouse->isActive());
    }

    public function testDalAllocationReservationAndAggregateProductStockSynchronization(): void
    {
        $container = static::getContainer();
        $context = Context::createDefaultContext();
        /** @var EntityRepository $warehouseRepository */
        $warehouseRepository = $container->get('medical_warehouse.repository');
        /** @var EntityRepository $stockRepository */
        $stockRepository = $container->get('medical_warehouse_stock.repository');
        /** @var EntityRepository $productRepository */
        $productRepository = $container->get('product.repository');

        $warehouseIds = [
            'DE-KOELN' => Uuid::randomHex(),
            'DE-BERLIN' => Uuid::randomHex(),
        ];

        $warehouseRepository->create([
            [
                'id' => $warehouseIds['DE-KOELN'],
                'code' => 'DAL-KOELN-' . strtoupper(substr($warehouseIds['DE-KOELN'], 0, 6)),
                'name' => 'DAL Köln',
                'countryCode' => 'DE',
                'priority' => 1,
                'isActive' => true,
            ],
            [
                'id' => $warehouseIds['DE-BERLIN'],
                'code' => 'DAL-BERLIN-' . strtoupper(substr($warehouseIds['DE-BERLIN'], 0, 6)),
                'name' => 'DAL Berlin',
                'countryCode' => 'DE',
                'priority' => 2,
                'isActive' => true,
            ],
        ], $context);

        $productBuilder = (new ProductBuilder(
            new IdsCollection(),
            'MED-DAL-' . strtoupper(substr(Uuid::randomHex(), 0, 8))
        ))->price(25);
        $productBuilder->write($container);
        $productId = $productBuilder->id;

        $warehouseCodes = [
            'DE-KOELN' => 'DAL-KOELN-' . strtoupper(substr($warehouseIds['DE-KOELN'], 0, 6)),
            'DE-BERLIN' => 'DAL-BERLIN-' . strtoupper(substr($warehouseIds['DE-BERLIN'], 0, 6)),
        ];

        $stockRepository->create([
            [
                'warehouseId' => $warehouseIds['DE-KOELN'],
                'productId' => $productId,
                'productVersionId' => Defaults::LIVE_VERSION,
                'quantity' => 15,
                'reservedQuantity' => 0,
                'minimumQuantity' => 5,
            ],
            [
                'warehouseId' => $warehouseIds['DE-BERLIN'],
                'productId' => $productId,
                'productVersionId' => Defaults::LIVE_VERSION,
                'quantity' => 12,
                'reservedQuantity' => 0,
                'minimumQuantity' => 3,
            ],
        ], $context);

        /** @var WarehouseAllocationService $allocationService */
        $allocationService = $container->get(WarehouseAllocationService::class);
        self::assertSame([
            ['warehouseCode' => $warehouseCodes['DE-KOELN'], 'priority' => 1, 'quantity' => 15],
            ['warehouseCode' => $warehouseCodes['DE-BERLIN'], 'priority' => 2, 'quantity' => 5],
        ], $allocationService->planForProduct($productId, 20, $context));

        /** @var WarehouseStockService $stockService */
        $stockService = $container->get(WarehouseStockService::class);
        $stockService->reserveStock(
            $warehouseCodes['DE-KOELN'],
            $productId,
            15,
            $context,
            'integration-test',
            'dal-order'
        );
        $stockService->reserveStock(
            $warehouseCodes['DE-BERLIN'],
            $productId,
            5,
            $context,
            'integration-test',
            'dal-order'
        );

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('productId', $productId));
        $criteria->addAssociation('warehouse');

        $stocks = $stockRepository->search($criteria, $context)->getEntities();
        $availableQuantities = [];

        foreach ($stocks as $stock) {
            if (!$stock instanceof WarehouseStockEntity || $stock->getWarehouse() === null) {
                continue;
            }

            $availableQuantities[$stock->getWarehouse()->getCode()] = $stock->getAvailableQuantity();
        }

        self::assertArrayHasKey($warehouseCodes['DE-KOELN'], $availableQuantities);
        self::assertArrayHasKey($warehouseCodes['DE-BERLIN'], $availableQuantities);
        self::assertSame(0, $availableQuantities[$warehouseCodes['DE-KOELN']]);
        self::assertSame(7, $availableQuantities[$warehouseCodes['DE-BERLIN']]);

        $product = $productRepository->search(new Criteria([$productId]), $context)->first();
        self::assertInstanceOf(ProductEntity::class, $product);
        self::assertSame(7, $product->getStock());

        $movementCount = (int) $container->get(Connection::class)->fetchOne(
            'SELECT COUNT(*) FROM medical_stock_movement WHERE product_id = :productId',
            ['productId' => Uuid::fromHexToBytes($productId)]
        );
        self::assertSame(2, $movementCount);
    }

    public function testShopwareOrderPersisterIsDecorated(): void
    {
        $persister = static::getContainer()->get(OrderPersister::class);

        self::assertInstanceOf(MedicalInventoryOrderPersister::class, $persister);
    }
}
