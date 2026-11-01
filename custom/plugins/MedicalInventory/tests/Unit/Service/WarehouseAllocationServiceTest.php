<?php declare(strict_types=1);

namespace MedicalInventory\Tests\Unit\Service;

use MedicalInventory\Core\Content\Warehouse\WarehouseEntity;
use MedicalInventory\Core\Content\WarehouseStock\WarehouseStockCollection;
use MedicalInventory\Core\Content\WarehouseStock\WarehouseStockEntity;
use MedicalInventory\Service\InsufficientWarehouseStockException;
use MedicalInventory\Service\WarehouseAllocationPlanner;
use MedicalInventory\Service\WarehouseAllocationService;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\Uuid\Uuid;

final class WarehouseAllocationServiceTest extends TestCase
{
    public function testItLoadsWarehouseAvailabilityAndPlansTheAllocation(): void
    {
        $repository = $this->createMock(EntityRepository::class);
        $searchResult = $this->createMock(EntitySearchResult::class);
        $searchResult->method('getEntities')->willReturn(new WarehouseStockCollection([
            $this->stock('DE-KOELN', 1, 15, 0),
            $this->stock('DE-BERLIN', 2, 12, 0),
        ]));
        $repository->expects(self::once())
            ->method('search')
            ->with(
                self::isInstanceOf(\Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria::class),
                self::isInstanceOf(Context::class)
            )
            ->willReturn($searchResult);

        $service = new WarehouseAllocationService($repository, new WarehouseAllocationPlanner());

        self::assertSame([
            ['warehouseCode' => 'DE-KOELN', 'priority' => 1, 'quantity' => 15],
            ['warehouseCode' => 'DE-BERLIN', 'priority' => 2, 'quantity' => 5],
        ], $service->planForProduct(Uuid::randomHex(), 20, Context::createDefaultContext()));
    }

    public function testItUsesAvailableQuantityAfterExistingReservations(): void
    {
        $repository = $this->createMock(EntityRepository::class);
        $searchResult = $this->createMock(EntitySearchResult::class);
        $searchResult->method('getEntities')->willReturn(new WarehouseStockCollection([
            $this->stock('DE-KOELN', 1, 15, 5),
        ]));
        $repository->method('search')->willReturn($searchResult);

        $service = new WarehouseAllocationService($repository, new WarehouseAllocationPlanner());

        self::assertSame([
            ['warehouseCode' => 'DE-KOELN', 'priority' => 1, 'quantity' => 10],
        ], $service->planForProduct(Uuid::randomHex(), 10, Context::createDefaultContext()));
    }

    public function testItDoesNotAllocateFromInactiveWarehouses(): void
    {
        $repository = $this->createMock(EntityRepository::class);
        $searchResult = $this->createMock(EntitySearchResult::class);
        $searchResult->method('getEntities')->willReturn(new WarehouseStockCollection([
            $this->stock('DE-KOELN', 1, 20, 0, false),
        ]));
        $repository->method('search')->willReturn($searchResult);

        $service = new WarehouseAllocationService($repository, new WarehouseAllocationPlanner());

        $this->expectException(InsufficientWarehouseStockException::class);

        $service->planForProduct(Uuid::randomHex(), 5, Context::createDefaultContext());
    }

    public function testItRejectsAnInvalidProductIdBeforeQueryingTheRepository(): void
    {
        $repository = $this->createMock(EntityRepository::class);
        $repository->expects(self::never())->method('search');

        $service = new WarehouseAllocationService($repository, new WarehouseAllocationPlanner());

        $this->expectException(\InvalidArgumentException::class);

        $service->planForProduct('invalid-id', 5, Context::createDefaultContext());
    }

    private function stock(
        string $warehouseCode,
        int $priority,
        int $quantity,
        int $reservedQuantity,
        bool $active = true,
    ): WarehouseStockEntity {
        $warehouse = new WarehouseEntity();
        $warehouse->setCode($warehouseCode);
        $warehouse->setName($warehouseCode);
        $warehouse->setCountryCode('DE');
        $warehouse->setPriority($priority);
        $warehouse->setIsActive($active);

        $stock = new WarehouseStockEntity();
        $stock->setWarehouse($warehouse);
        $stock->setQuantity($quantity);
        $stock->setReservedQuantity($reservedQuantity);

        return $stock;
    }
}
