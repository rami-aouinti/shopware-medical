<?php declare(strict_types=1);

namespace MedicalInventory\Core\Content\WarehouseStock;

use MedicalInventory\Core\Content\Warehouse\WarehouseEntity;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;

class WarehouseStockEntity extends Entity
{
    protected string $warehouseId;

    protected string $productId;

    protected string $productVersionId;

    protected int $quantity;

    protected int $reservedQuantity;

    protected ?WarehouseEntity $warehouse = null;

    protected ?ProductEntity $product = null;

    public function getWarehouseId(): string
    {
        return $this->warehouseId;
    }

    public function setWarehouseId(string $warehouseId): void
    {
        $this->warehouseId = $warehouseId;
    }

    public function getProductId(): string
    {
        return $this->productId;
    }

    public function setProductId(string $productId): void
    {
        $this->productId = $productId;
    }

    public function getProductVersionId(): string
    {
        return $this->productVersionId;
    }

    public function setProductVersionId(string $productVersionId): void
    {
        $this->productVersionId = $productVersionId;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): void
    {
        $this->quantity = $quantity;
    }

    public function getReservedQuantity(): int
    {
        return $this->reservedQuantity;
    }

    public function setReservedQuantity(int $reservedQuantity): void
    {
        $this->reservedQuantity = $reservedQuantity;
    }

    public function getAvailableQuantity(): int
    {
        return $this->quantity - $this->reservedQuantity;
    }

    public function getWarehouse(): ?WarehouseEntity
    {
        return $this->warehouse;
    }

    public function setWarehouse(?WarehouseEntity $warehouse): void
    {
        $this->warehouse = $warehouse;
    }

    public function getProduct(): ?ProductEntity
    {
        return $this->product;
    }

    public function setProduct(?ProductEntity $product): void
    {
        $this->product = $product;
    }
}
