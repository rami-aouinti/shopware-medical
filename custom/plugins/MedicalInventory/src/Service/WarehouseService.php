<?php declare(strict_types=1);

namespace MedicalInventory\Service;

use MedicalInventory\Core\Content\Warehouse\WarehouseEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;
use RuntimeException;

class WarehouseService
{
    public function __construct(
        private readonly EntityRepository $warehouseRepository,
    ) {
    }

    public function create(
        string $code,
        string $name,
        string $countryCode,
        Context $context,
    ): void {
        $this->warehouseRepository->create([
            [
                'id' => Uuid::randomHex(),
                'code' => $code,
                'name' => $name,
                'countryCode' => strtoupper($countryCode),
                'isActive' => true,
            ],
        ], $context);
    }

    public function findByCode(string $code, Context $context): ?WarehouseEntity
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('code', $code));
        $criteria->setLimit(1);

        $entity = $this->warehouseRepository->search($criteria, $context)->first();

        return $entity instanceof WarehouseEntity ? $entity : null;
    }

    public function list(Context $context): EntitySearchResult
    {
        $criteria = new Criteria();
        $criteria->addSorting(new \Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting('name'));

        return $this->warehouseRepository->search($criteria, $context);
    }

    public function setActiveByCode(string $code, bool $active, Context $context): void
    {
        $warehouse = $this->findByCode($code, $context);

        if ($warehouse === null) {
            throw new RuntimeException(sprintf('Warehouse "%s" was not found.', $code));
        }

        $this->warehouseRepository->update([
            [
                'id' => $warehouse->getId(),
                'isActive' => $active,
            ],
        ], $context);
    }

    public function deleteByCode(string $code, Context $context): void
    {
        $warehouse = $this->findByCode($code, $context);

        if ($warehouse === null) {
            throw new RuntimeException(sprintf('Warehouse "%s" was not found.', $code));
        }

        $this->warehouseRepository->delete([
            ['id' => $warehouse->getId()],
        ], $context);
    }
}
