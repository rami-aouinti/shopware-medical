<?php declare(strict_types=1);

namespace MedicalInventory\Service;

use DomainException;
use InvalidArgumentException;

/**
 * Contains the stock arithmetic and business invariants.
 *
 * A stock row always satisfies:
 *   quantity >= 0
 *   reservedQuantity >= 0
 *   reservedQuantity <= quantity
 */
final class WarehouseStockCalculator
{
    public const MAX_QUANTITY = 4_294_967_295;

    /**
     * @return array{quantity: int, reservedQuantity: int}
     */
    public function setQuantity(int $currentQuantity, int $reservedQuantity, int $newQuantity): array
    {
        $this->assertQuantity($newQuantity);

        if ($newQuantity < $reservedQuantity) {
            throw new DomainException(sprintf(
                'Quantity cannot be lower than the reserved quantity (%d).',
                $reservedQuantity
            ));
        }

        return ['quantity' => $newQuantity, 'reservedQuantity' => $reservedQuantity];
    }

    /**
     * @return array{quantity: int, reservedQuantity: int}
     */
    public function add(int $quantity, int $reservedQuantity, int $amount): array
    {
        $this->assertPositiveAmount($amount);

        if ($quantity > self::MAX_QUANTITY - $amount) {
            throw new DomainException('The resulting stock quantity exceeds the database limit.');
        }

        return ['quantity' => $quantity + $amount, 'reservedQuantity' => $reservedQuantity];
    }

    /**
     * @return array{quantity: int, reservedQuantity: int}
     */
    public function remove(int $quantity, int $reservedQuantity, int $amount): array
    {
        $this->assertPositiveAmount($amount);
        $available = $quantity - $reservedQuantity;

        if ($amount > $available) {
            throw new DomainException(sprintf(
                'Cannot remove %d units: only %d units are available.',
                $amount,
                $available
            ));
        }

        return ['quantity' => $quantity - $amount, 'reservedQuantity' => $reservedQuantity];
    }

    /**
     * @return array{quantity: int, reservedQuantity: int}
     */
    public function reserve(int $quantity, int $reservedQuantity, int $amount): array
    {
        $this->assertPositiveAmount($amount);
        $available = $quantity - $reservedQuantity;

        if ($amount > $available) {
            throw new DomainException(sprintf(
                'Cannot reserve %d units: only %d units are available.',
                $amount,
                $available
            ));
        }

        return ['quantity' => $quantity, 'reservedQuantity' => $reservedQuantity + $amount];
    }

    /**
     * @return array{quantity: int, reservedQuantity: int}
     */
    public function release(int $quantity, int $reservedQuantity, int $amount): array
    {
        $this->assertPositiveAmount($amount);

        if ($amount > $reservedQuantity) {
            throw new DomainException(sprintf(
                'Cannot release %d units: only %d units are reserved.',
                $amount,
                $reservedQuantity
            ));
        }

        return ['quantity' => $quantity, 'reservedQuantity' => $reservedQuantity - $amount];
    }

    /**
     * Ship reserved units: both physical quantity and reservation decrease.
     *
     * @return array{quantity: int, reservedQuantity: int}
     */
    public function ship(int $quantity, int $reservedQuantity, int $amount): array
    {
        $this->assertPositiveAmount($amount);

        if ($amount > $reservedQuantity) {
            throw new DomainException(sprintf(
                'Cannot ship %d units: only %d units are reserved.',
                $amount,
                $reservedQuantity
            ));
        }

        return [
            'quantity' => $quantity - $amount,
            'reservedQuantity' => $reservedQuantity - $amount,
        ];
    }

    private function assertPositiveAmount(int $amount): void
    {
        if ($amount <= 0) {
            throw new InvalidArgumentException('The quantity must be greater than zero.');
        }

        if ($amount > self::MAX_QUANTITY) {
            throw new InvalidArgumentException('The quantity exceeds the database limit.');
        }
    }

    private function assertQuantity(int $quantity): void
    {
        if ($quantity < 0 || $quantity > self::MAX_QUANTITY) {
            throw new InvalidArgumentException(sprintf(
                'Quantity must be between 0 and %d.',
                self::MAX_QUANTITY
            ));
        }
    }
}
