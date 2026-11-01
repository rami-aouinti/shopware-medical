<?php declare(strict_types=1);

namespace MedicalInventory\Service;

use DomainException;

final class InsufficientWarehouseStockException extends DomainException
{
    public function __construct(
        private readonly int $requestedQuantity,
        private readonly int $availableQuantity,
    ) {
        parent::__construct(sprintf(
            'Insufficient warehouse stock: requested %d units, only %d units are available.',
            $requestedQuantity,
            $availableQuantity
        ));
    }

    public function getRequestedQuantity(): int
    {
        return $this->requestedQuantity;
    }

    public function getAvailableQuantity(): int
    {
        return $this->availableQuantity;
    }
}
