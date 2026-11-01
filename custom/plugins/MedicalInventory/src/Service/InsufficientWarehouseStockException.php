<?php declare(strict_types=1);

namespace MedicalInventory\Service;

use Shopware\Core\Checkout\Cart\CartException;
use Symfony\Component\HttpFoundation\Response;

final class InsufficientWarehouseStockException extends CartException
{
    public const ERROR_CODE = 'MEDICAL_INVENTORY__INSUFFICIENT_WAREHOUSE_STOCK';

    public function __construct(
        private readonly int $requestedQuantity,
        private readonly int $availableQuantity,
    ) {
        parent::__construct(
            Response::HTTP_CONFLICT,
            self::ERROR_CODE,
            'Insufficient warehouse stock: requested {{ requestedQuantity }} units, only {{ availableQuantity }} units are available.',
            [
                'requestedQuantity' => $requestedQuantity,
                'availableQuantity' => $availableQuantity,
            ]
        );
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
