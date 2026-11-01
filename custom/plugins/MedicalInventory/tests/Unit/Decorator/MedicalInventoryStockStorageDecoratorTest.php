<?php declare(strict_types=1);

namespace MedicalInventory\Tests\Unit\Decorator;

use Doctrine\DBAL\Connection;
use MedicalInventory\Decorator\MedicalInventoryStockStorageDecorator;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Product\Stock\AbstractStockStorage;
use Shopware\Core\Content\Product\Stock\StockAlteration;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;

final class MedicalInventoryStockStorageDecoratorTest extends TestCase
{
    public function testItLeavesManagedProductStockChangesToMedicalInventory(): void
    {
        $managedProductId = Uuid::randomHex();
        $nativeProductId = Uuid::randomHex();
        $managedChange = new StockAlteration(Uuid::randomHex(), $managedProductId, 0, 3);
        $nativeChange = new StockAlteration(Uuid::randomHex(), $nativeProductId, 0, 2);
        $context = Context::createDefaultContext();

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects(self::once())
            ->method('fetchFirstColumn')
            ->willReturn([strtolower($managedProductId)]);

        $decorated = $this->createMock(AbstractStockStorage::class);
        $decorated
            ->expects(self::once())
            ->method('alter')
            ->with([$nativeChange], $context);

        $storage = new MedicalInventoryStockStorageDecorator($decorated, $connection);
        $storage->alter([$managedChange, $nativeChange], $context);
    }

    public function testItDelegatesAllChangesWhenNoProductsAreManaged(): void
    {
        $change = new StockAlteration(Uuid::randomHex(), Uuid::randomHex(), 0, 1);
        $context = Context::createDefaultContext();

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects(self::once())
            ->method('fetchFirstColumn')
            ->willReturn([]);

        $decorated = $this->createMock(AbstractStockStorage::class);
        $decorated
            ->expects(self::once())
            ->method('alter')
            ->with([$change], $context);

        $storage = new MedicalInventoryStockStorageDecorator($decorated, $connection);
        $storage->alter([$change], $context);
    }
}
