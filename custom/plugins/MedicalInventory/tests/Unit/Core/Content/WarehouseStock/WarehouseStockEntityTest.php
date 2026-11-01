<?php declare(strict_types=1);

namespace MedicalInventory\Tests\Unit\Core\Content\WarehouseStock;

use MedicalInventory\Core\Content\WarehouseStock\WarehouseStockEntity;
use PHPUnit\Framework\TestCase;

final class WarehouseStockEntityTest extends TestCase
{
    public function testItCalculatesAvailableQuantityFromPhysicalAndReservedStock(): void
    {
        $stock = new WarehouseStockEntity();
        $stock->setQuantity(120);
        $stock->setReservedQuantity(35);

        self::assertSame(85, $stock->getAvailableQuantity());
    }
}
