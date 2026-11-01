<?php declare(strict_types=1);

namespace MedicalInventory\Service;

use MedicalInventory\Core\Content\Warehouse\WarehouseEntity;
use RuntimeException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;

final class WarehouseService
{
    public function __construct(
        private readonly EntityRepository $warehouseRepository,
        private readonly WarehouseValidator $warehouseValidator,
    ) {
    }

    public function create(
        string $code,
        string $name,
        string $countryCode,
        Context $context,
    ): void {
        $data = $this->warehouseValidator->validate($code, $name, $countryCode);

        if ($this->findByCode($data['code'], $context) instanceof WarehouseEntity) {
            throw new RuntimeException(sprintf('A warehouse with code "%s" already exists.', $data['code']));
        }

        $this->warehouseRepository->create([
            [
                'id' => Uuid::randomHex(),
                'code' => $data['code'],
                'name' => $data['name'],
                'countryCode' => $data['countryCode'],
                'isActive' => true,
            ],
        ], $context);
    }

    public function findByCode(string $code, Context $context): ?WarehouseEntity
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('code', strtoupper(trim($code))));
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

        if (!$warehouse instanceof WarehouseEntity) {
            throw new RuntimeException(sprintf('Warehouse "%s" was not found.', $code));
        }

        $this->warehouseRepository->update([
            [
                'id' => $warehouse->getUniqueIdentifier(),
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
            ['id' => $warehouse->getUniqueIdentifier()],
        ], $context);
    }
}
