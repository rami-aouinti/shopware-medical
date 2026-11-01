<?php declare(strict_types=1);

namespace MedicalInventory\Service;

use Doctrine\DBAL\Connection;
use DomainException;
use InvalidArgumentException;
use MedicalInventory\Core\Content\Warehouse\WarehouseEntity;
use MedicalInventory\Core\Content\WarehouseStock\WarehouseStockEntity;
use RuntimeException;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;

/**
 * Application service for stock operations.
 *
 * Mutations lock an existing stock row inside a DB transaction. This prevents
 * two concurrent reservations/removals from reading and overwriting the same
 * existing quantity. The database unique index remains the final guard when
 * two requests try to create the first row at the same time.
 */
final class WarehouseStockService
{
    public function __construct(
        private readonly EntityRepository $warehouseRepository,
        private readonly EntityRepository $warehouseStockRepository,
        private readonly EntityRepository $productRepository,
        private readonly Connection $connection,
        private readonly WarehouseStockCalculator $calculator,
        private readonly StockMovementRecorder $movementRecorder,
    ) {
    }

    public function setStock(
        string $warehouseCode,
        string $productId,
        int $quantity,
        Context $context,
    ): WarehouseStockEntity {
        return $this->mutate(
            $warehouseCode,
            $productId,
            $context,
            fn (int $current, int $reserved): array => $this->calculator->setQuantity($current, $reserved, $quantity),
            'ADJUSTMENT',
            'Physical stock quantity set',
        );
    }

    public function addStock(
        string $warehouseCode,
        string $productId,
        int $amount,
        Context $context,
    ): WarehouseStockEntity {
        return $this->mutate(
            $warehouseCode,
            $productId,
            $context,
            fn (int $current, int $reserved): array => $this->calculator->add($current, $reserved, $amount),
            'INBOUND',
            'Stock replenishment',
        );
    }

    public function removeStock(
        string $warehouseCode,
        string $productId,
        int $amount,
        Context $context,
    ): WarehouseStockEntity {
        return $this->mutate(
            $warehouseCode,
            $productId,
            $context,
            fn (int $current, int $reserved): array => $this->calculator->remove($current, $reserved, $amount),
            'OUTBOUND',
            'Manual stock removal',
        );
    }

    public function reserveStock(
        string $warehouseCode,
        string $productId,
        int $amount,
        Context $context,
        ?string $referenceType = null,
        ?string $referenceId = null,
    ): WarehouseStockEntity {
        return $this->mutate(
            $warehouseCode,
            $productId,
            $context,
            fn (int $current, int $reserved): array => $this->calculator->reserve($current, $reserved, $amount),
            'RESERVATION',
            'Order stock reservation',
            true,
            $referenceType,
            $referenceId,
        );
    }

    public function releaseReservation(
        string $warehouseCode,
        string $productId,
        int $amount,
        Context $context,
        ?string $referenceType = null,
        ?string $referenceId = null,
    ): WarehouseStockEntity {
        return $this->mutate(
            $warehouseCode,
            $productId,
            $context,
            fn (int $current, int $reserved): array => $this->calculator->release($current, $reserved, $amount),
            'RELEASE',
            'Stock reservation released',
            false,
            $referenceType,
            $referenceId,
        );
    }

    public function shipReservedStock(
        string $warehouseCode,
        string $productId,
        int $amount,
        Context $context,
        ?string $referenceType = null,
        ?string $referenceId = null,
    ): WarehouseStockEntity {
        return $this->mutate(
            $warehouseCode,
            $productId,
            $context,
            fn (int $current, int $reserved): array => $this->calculator->ship($current, $reserved, $amount),
            'SHIPMENT',
            'Reserved stock shipped',
            false,
            $referenceType,
            $referenceId,
        );
    }

    public function setMinimumQuantity(
        string $warehouseCode,
        string $productId,
        int $minimumQuantity,
        Context $context,
    ): void {
        if ($minimumQuantity < 0 || $minimumQuantity > WarehouseStockCalculator::MAX_QUANTITY) {
            throw new InvalidArgumentException('Minimum quantity is outside the supported range.');
        }

        $warehouse = $this->getWarehouse($warehouseCode, $context);
        $this->assertProductExists($productId, $context);
        $warehouseId = $warehouse->getUniqueIdentifier();
        $versionId = $context->getVersionId();

        $this->connection->transactional(function () use (
            $warehouseId,
            $productId,
            $versionId,
            $minimumQuantity,
            $context
        ): void {
            $row = $this->connection->fetchAssociative(
                <<<'SQL'
                    SELECT LOWER(HEX(id)) AS id
                    FROM medical_warehouse_stock
                    WHERE warehouse_id = :warehouseId
                      AND product_id = :productId
                      AND product_version_id = :versionId
                    FOR UPDATE
                SQL,
                [
                    'warehouseId' => Uuid::fromHexToBytes($warehouseId),
                    'productId' => Uuid::fromHexToBytes($productId),
                    'versionId' => Uuid::fromHexToBytes($versionId),
                ],
            );

            if ($row === false) {
                $this->warehouseStockRepository->create([[
                    'id' => Uuid::randomHex(),
                    'warehouseId' => $warehouseId,
                    'productId' => $productId,
                    'productVersionId' => $versionId,
                    'quantity' => 0,
                    'reservedQuantity' => 0,
                    'minimumQuantity' => $minimumQuantity,
                ]], $context);

                return;
            }

            $this->warehouseStockRepository->update([[
                'id' => (string) $row['id'],
                'minimumQuantity' => $minimumQuantity,
            ]], $context);
        });
    }

    public function getStock(
        string $warehouseCode,
        string $productId,
        Context $context,
    ): ?WarehouseStockEntity {
        $warehouse = $this->getWarehouse($warehouseCode, $context);
        $this->assertProductId($productId);

        return $this->findStock($warehouse->getUniqueIdentifier(), $productId, $context);
    }

    public function listByWarehouse(
        string $warehouseCode,
        Context $context,
    ): EntitySearchResult {
        $warehouse = $this->getWarehouse($warehouseCode, $context);
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('warehouseId', $warehouse->getUniqueIdentifier()));
        $criteria->addAssociation('product');

        return $this->warehouseStockRepository->search($criteria, $context);
    }

    public function getWarehouseWithStocks(
        string $warehouseCode,
        Context $context,
    ): ?WarehouseEntity {
        $warehouse = $this->getWarehouse($warehouseCode, $context);
        $criteria = new Criteria([$warehouse->getUniqueIdentifier()]);
        $criteria->addAssociation('stocks.product');

        $entity = $this->warehouseRepository->search($criteria, $context)->first();

        return $entity instanceof WarehouseEntity ? $entity : null;
    }

    /**
     * @param callable(int, int): array{quantity: int, reservedQuantity: int} $change
     */
    private function mutate(
        string $warehouseCode,
        string $productId,
        Context $context,
        callable $change,
        string $movementType,
        string $reason,
        bool $requireActive = true,
        ?string $referenceType = null,
        ?string $referenceId = null,
    ): WarehouseStockEntity {
        $warehouse = $this->getWarehouse($warehouseCode, $context);

        if ($requireActive && !$warehouse->isActive()) {
            throw new DomainException(sprintf('Warehouse "%s" is inactive.', $warehouseCode));
        }

        $this->assertProductExists($productId, $context);

        $warehouseId = $warehouse->getUniqueIdentifier();
        $versionId = $context->getVersionId();

        return $this->connection->transactional(function () use (
            $warehouseId,
            $productId,
            $versionId,
            $context,
            $change,
            $movementType,
            $reason,
            $referenceType,
            $referenceId,
        ): WarehouseStockEntity {
            // Lock the current row before calculating the new values.
            $row = $this->connection->fetchAssociative(
                <<<'SQL'
                    SELECT LOWER(HEX(id)) AS id, quantity, reserved_quantity
                    FROM medical_warehouse_stock
                    WHERE warehouse_id = :warehouseId
                      AND product_id = :productId
                      AND product_version_id = :versionId
                    FOR UPDATE
                SQL,
                [
                    'warehouseId' => Uuid::fromHexToBytes($warehouseId),
                    'productId' => Uuid::fromHexToBytes($productId),
                    'versionId' => Uuid::fromHexToBytes($versionId),
                ],
            );

            $currentQuantity = $row === false ? 0 : (int) $row['quantity'];
            $reservedQuantity = $row === false ? 0 : (int) $row['reserved_quantity'];
            $newState = $change($currentQuantity, $reservedQuantity);

            if ($row === false) {
                $this->warehouseStockRepository->create([
                    [
                        'id' => Uuid::randomHex(),
                        'warehouseId' => $warehouseId,
                        'productId' => $productId,
                        'productVersionId' => $versionId,
                        'quantity' => $newState['quantity'],
                        'reservedQuantity' => $newState['reservedQuantity'],
                        'minimumQuantity' => 0,
                    ],
                ], $context);
            }

            if ($row !== false) {
                $this->warehouseStockRepository->update([
                    [
                        'id' => (string) $row['id'],
                        'quantity' => $newState['quantity'],
                        'reservedQuantity' => $newState['reservedQuantity'],
                    ],
                ], $context);
            }

            $this->movementRecorder->record(
                $warehouseId,
                $productId,
                $versionId,
                $movementType,
                $newState['quantity'] - $currentQuantity,
                $newState['reservedQuantity'] - $reservedQuantity,
                $reason,
                $referenceType,
                $referenceId
            );

            $stock = $this->findStockByIds($warehouseId, $productId, $versionId, $context);

            if (!$stock instanceof WarehouseStockEntity) {
                throw new RuntimeException('The stock row could not be reloaded after the operation.');
            }

            return $stock;
        });
    }

    private function getWarehouse(string $code, Context $context): WarehouseEntity
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('code', strtoupper(trim($code))));
        $criteria->setLimit(1);

        $warehouse = $this->warehouseRepository->search($criteria, $context)->first();

        if (!$warehouse instanceof WarehouseEntity) {
            throw new RuntimeException(sprintf('Warehouse "%s" was not found.', $code));
        }

        return $warehouse;
    }

    private function assertProductExists(string $productId, Context $context): void
    {
        $this->assertProductId($productId);

        $product = $this->productRepository->search(new Criteria([$productId]), $context)->first();

        if (!$product instanceof ProductEntity) {
            throw new InvalidArgumentException(sprintf('Product "%s" was not found in this version.', $productId));
        }
    }

    private function assertProductId(string $productId): void
    {
        if (!Uuid::isValid($productId)) {
            throw new InvalidArgumentException('Product ID must be a valid Shopware UUID in hexadecimal format.');
        }
    }

    private function findStock(
        string $warehouseId,
        string $productId,
        Context $context,
    ): ?WarehouseStockEntity {
        return $this->findStockByIds($warehouseId, $productId, $context->getVersionId(), $context);
    }

    private function findStockByIds(
        string $warehouseId,
        string $productId,
        string $versionId,
        Context $context,
    ): ?WarehouseStockEntity {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('warehouseId', $warehouseId));
        $criteria->addFilter(new EqualsFilter('productId', $productId));
        $criteria->addFilter(new EqualsFilter('productVersionId', $versionId));
        $criteria->setLimit(1);
        $criteria->addAssociation('product');

        $stock = $this->warehouseStockRepository->search($criteria, $context)->first();

        return $stock instanceof WarehouseStockEntity ? $stock : null;
    }
}
