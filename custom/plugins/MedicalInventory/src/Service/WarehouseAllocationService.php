<?php declare(strict_types=1);

namespace MedicalInventory\Service;

use InvalidArgumentException;
use MedicalInventory\Core\Content\Warehouse\WarehouseEntity;
use MedicalInventory\Core\Content\WarehouseStock\WarehouseStockEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;

/**
 * Loads per-warehouse availability from the Shopware DAL and delegates
 * the allocation decision to the pure planner.
 *
 * This service plans allocations only. Order persistence and stock reservation
 * are intentionally handled later by the order/inventory workflow.
 */
final class WarehouseAllocationService
{
    public function __construct(
        private readonly EntityRepository $warehouseStockRepository,
        private readonly WarehouseAllocationPlanner $planner,
    ) {
    }

    /**
     * @return list<array{warehouseCode: string, priority: int, quantity: int}>
     */
    public function planForProduct(
        string $productId,
        int $requestedQuantity,
        Context $context,
    ): array {
        if (!Uuid::isValid($productId)) {
            throw new InvalidArgumentException('Product ID must be a valid Shopware UUID in hexadecimal format.');
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('productId', $productId));
        $criteria->addFilter(new EqualsFilter('productVersionId', $context->getVersionId()));
        $criteria->addAssociation('warehouse');

        $stocks = $this->warehouseStockRepository->search($criteria, $context)->getEntities();
        $candidates = [];

        foreach ($stocks as $stock) {
            if (!$stock instanceof WarehouseStockEntity) {
                continue;
            }

            $warehouse = $stock->getWarehouse();

            if (!$warehouse instanceof WarehouseEntity) {
                continue;
            }

            $candidates[] = [
                'warehouseCode' => $warehouse->getCode(),
                'priority' => $warehouse->getPriority(),
                'availableQuantity' => $stock->getAvailableQuantity(),
                'active' => $warehouse->isActive(),
            ];
        }

        return $this->planner->plan($requestedQuantity, $candidates);
    }
}
