<?php declare(strict_types=1);

namespace MedicalInventory\Tests\Unit\Service;

use DomainException;
use InvalidArgumentException;
use MedicalInventory\Service\WarehouseStockCalculator;
use PHPUnit\Framework\TestCase;

final class WarehouseStockCalculatorTest extends TestCase
{
    private WarehouseStockCalculator $calculator;

    #[\Override]
    protected function setUp(): void
    {
        $this->calculator = new WarehouseStockCalculator();
    }

    public function testItSetsThePhysicalQuantityWithoutChangingReservations(): void
    {
        self::assertSame(
            ['quantity' => 30, 'reservedQuantity' => 8],
            $this->calculator->setQuantity(20, 8, 30)
        );
    }

    public function testItCannotSetQuantityBelowReservedQuantity(): void
    {
        $this->expectException(DomainException::class);

        $this->calculator->setQuantity(20, 8, 7);
    }

    public function testItAddsStock(): void
    {
        self::assertSame(
            ['quantity' => 25, 'reservedQuantity' => 4],
            $this->calculator->add(20, 4, 5)
        );
    }

    public function testItCannotRemoveReservedUnits(): void
    {
        $this->expectException(DomainException::class);

        $this->calculator->remove(10, 7, 4);
    }

    public function testItReservesOnlyAvailableUnits(): void
    {
        self::assertSame(
            ['quantity' => 10, 'reservedQuantity' => 8],
            $this->calculator->reserve(10, 3, 5)
        );
    }

    public function testItCannotReserveMoreThanAvailable(): void
    {
        $this->expectException(DomainException::class);

        $this->calculator->reserve(10, 8, 3);
    }

    public function testItReleasesAnExistingReservation(): void
    {
        self::assertSame(
            ['quantity' => 10, 'reservedQuantity' => 2],
            $this->calculator->release(10, 7, 5)
        );
    }

    public function testShippingDecreasesBothPhysicalAndReservedQuantities(): void
    {
        self::assertSame(
            ['quantity' => 7, 'reservedQuantity' => 2],
            $this->calculator->ship(10, 5, 3)
        );
    }

    public function testItRejectsZeroOrNegativeMovements(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->calculator->add(10, 0, 0);
    }

    public function testItRejectsQuantitiesAboveTheDatabaseLimit(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->calculator->setQuantity(0, 0, WarehouseStockCalculator::MAX_QUANTITY + 1);
    }
}
