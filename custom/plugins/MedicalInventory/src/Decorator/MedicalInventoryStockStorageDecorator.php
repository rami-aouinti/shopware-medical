<?php declare(strict_types=1);

namespace MedicalInventory\Decorator;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Shopware\Core\Content\Product\Stock\AbstractStockStorage;
use Shopware\Core\Content\Product\Stock\StockAlteration;
use Shopware\Core\Content\Product\Stock\StockDataCollection;
use Shopware\Core\Content\Product\Stock\StockLoadRequest;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

final class MedicalInventoryStockStorageDecorator extends AbstractStockStorage
{
    public function __construct(
        private readonly AbstractStockStorage $decorated,
        private readonly Connection $connection,
    ) {
    }

    #[\Override]
    public function getDecorated(): AbstractStockStorage
    {
        return $this->decorated;
    }

    #[\Override]
    public function load(StockLoadRequest $stockRequest, SalesChannelContext $context): StockDataCollection
    {
        return $this->decorated->load($stockRequest, $context);
    }

    /**
     * MedicalInventory owns stock alterations for managed products.
     * Native Shopware stock management remains active for every other product.
     *
     * @param list<StockAlteration> $changes
     */
    #[\Override]
    public function alter(array $changes, Context $context): void
    {
        if ($changes === [] || $context->getVersionId() !== Defaults::LIVE_VERSION) {
            $this->decorated->alter($changes, $context);

            return;
        }

        $productIds = array_values(array_unique(array_map(
            static fn (StockAlteration $change): string => strtolower($change->productId),
            $changes
        )));

        $managedProductIds = $this->connection->fetchFirstColumn(
            <<<'SQL'
                SELECT DISTINCT LOWER(HEX(product_id))
                FROM medical_warehouse_stock
                WHERE product_id IN (:productIds)
                  AND product_version_id = :versionId
            SQL,
            [
                'productIds' => Uuid::fromHexToBytesList($productIds),
                'versionId' => Uuid::fromHexToBytes(Defaults::LIVE_VERSION),
            ],
            ['productIds' => ArrayParameterType::BINARY]
        );

        $managedLookup = array_fill_keys(
            array_map(static fn (mixed $id): string => strtolower((string) $id), $managedProductIds),
            true
        );

        $nativeChanges = array_values(array_filter(
            $changes,
            static fn (StockAlteration $change): bool => !isset($managedLookup[strtolower($change->productId)])
        ));

        $this->decorated->alter($nativeChanges, $context);
    }

    /**
     * Keep Shopware's availability indexing; product.stock is synchronized
     * from warehouse availability by WarehouseStockService.
     *
     * @param list<string> $productIds
     */
    #[\Override]
    public function index(array $productIds, Context $context): void
    {
        $this->decorated->index($productIds, $context);
    }
}
