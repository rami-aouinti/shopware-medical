<?php declare(strict_types=1);

namespace MedicalInventory\Core\Content\Warehouse;

use MedicalInventory\Core\Content\WarehouseStock\WarehouseStockCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;

class WarehouseEntity extends Entity
{
    protected string $code;

    protected string $name;

    protected string $countryCode;

    protected bool $isActive;

    protected ?WarehouseStockCollection $stocks = null;

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): void
    {
        $this->code = $code;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getCountryCode(): string
    {
        return $this->countryCode;
    }

    public function setCountryCode(string $countryCode): void
    {
        $this->countryCode = $countryCode;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): void
    {
        $this->isActive = $isActive;
    }

    public function getStocks(): ?WarehouseStockCollection
    {
        return $this->stocks;
    }

    public function setStocks(?WarehouseStockCollection $stocks): void
    {
        $this->stocks = $stocks;
    }
}
