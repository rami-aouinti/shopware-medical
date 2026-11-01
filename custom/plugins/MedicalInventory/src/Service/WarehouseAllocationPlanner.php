<?php declare(strict_types=1);

namespace MedicalInventory\Service;

use InvalidArgumentException;

/**
 * Plans stock allocation without changing inventory or creating reservations.
 *
 * Candidates are ordered by priority (ascending), then available stock
 * (descending), then warehouse code (ascending) for deterministic results.
 */
final class WarehouseAllocationPlanner
{
    /**
     * @param list<array{warehouseCode: string, priority: int, availableQuantity: int, active: bool}> $warehouses
     *
     * @return list<array{warehouseCode: string, priority: int, quantity: int}>
     */
    public function plan(int $requestedQuantity, array $warehouses): array
    {
        $this->assertRequestedQuantity($requestedQuantity);
        $candidates = $this->prepareCandidates($warehouses);
        $singleWarehouseAllocation = $this->findSingleWarehouseAllocation($candidates, $requestedQuantity);

        if ($singleWarehouseAllocation !== null) {
            return [$singleWarehouseAllocation];
        }

        return $this->splitAllocation($candidates, $requestedQuantity);
    }

    private function assertRequestedQuantity(int $requestedQuantity): void
    {
        if ($requestedQuantity <= 0 || $requestedQuantity > WarehouseStockCalculator::MAX_QUANTITY) {
            throw new InvalidArgumentException('Requested quantity must be between 1 and 4294967295.');
        }
    }

    /**
     * @param list<array{warehouseCode: string, priority: int, availableQuantity: int, active: bool}> $warehouses
     *
     * @return list<array{warehouseCode: string, priority: int, availableQuantity: int, active: bool}>
     */
    private function prepareCandidates(array $warehouses): array
    {
        $candidates = [];
        $seenCodes = [];

        foreach ($warehouses as $warehouse) {
            $this->assertCandidate($warehouse);
            $code = strtoupper(trim($warehouse['warehouseCode']));

            if (isset($seenCodes[$code])) {
                throw new InvalidArgumentException(sprintf('Duplicate warehouse candidate "%s".', $code));
            }

            $seenCodes[$code] = true;

            if (!$warehouse['active'] || $warehouse['availableQuantity'] === 0) {
                continue;
            }

            $warehouse['warehouseCode'] = $code;
            $candidates[] = $warehouse;
        }

        usort($candidates, self::compareCandidates(...));

        return $candidates;
    }

    /**
     * @param list<array{warehouseCode: string, priority: int, availableQuantity: int, active: bool}> $candidates
     *
     * @return array{warehouseCode: string, priority: int, quantity: int}|null
     */
    private function findSingleWarehouseAllocation(array $candidates, int $requestedQuantity): ?array
    {
        foreach ($candidates as $candidate) {
            if ($candidate['availableQuantity'] >= $requestedQuantity) {
                return [
                    'warehouseCode' => $candidate['warehouseCode'],
                    'priority' => $candidate['priority'],
                    'quantity' => $requestedQuantity,
                ];
            }
        }

        return null;
    }

    /**
     * @param list<array{warehouseCode: string, priority: int, availableQuantity: int, active: bool}> $candidates
     *
     * @return list<array{warehouseCode: string, priority: int, quantity: int}>
     */
    private function splitAllocation(array $candidates, int $requestedQuantity): array
    {
        $remaining = $requestedQuantity;
        $allocations = [];

        foreach ($candidates as $candidate) {
            $allocatedQuantity = min($candidate['availableQuantity'], $remaining);

            if ($allocatedQuantity === 0) {
                continue;
            }

            $allocations[] = [
                'warehouseCode' => $candidate['warehouseCode'],
                'priority' => $candidate['priority'],
                'quantity' => $allocatedQuantity,
            ];

            $remaining -= $allocatedQuantity;

            if ($remaining === 0) {
                return $allocations;
            }
        }

        throw new InsufficientWarehouseStockException(
            $requestedQuantity,
            $requestedQuantity - $remaining
        );
    }

    /**
     * @param array{warehouseCode: string, priority: int, availableQuantity: int, active: bool} $candidate
     */
    private function assertCandidate(array $candidate): void
    {
        $this->assertWarehouseCode($candidate);
        $this->assertPriority($candidate);
        $this->assertAvailableQuantity($candidate);
        $this->assertActiveFlag($candidate);
    }

    /**
     * @param array<string, mixed> $candidate
     */
    private function assertWarehouseCode(array $candidate): void
    {
        if (
            !isset($candidate['warehouseCode'])
            || !is_string($candidate['warehouseCode'])
            || trim($candidate['warehouseCode']) === ''
        ) {
            throw new InvalidArgumentException('Warehouse allocation candidate must have a warehouse code.');
        }
    }

    /**
     * @param array<string, mixed> $candidate
     */
    private function assertPriority(array $candidate): void
    {
        if (
            !isset($candidate['priority'])
            || !is_int($candidate['priority'])
            || $candidate['priority'] < 0
            || $candidate['priority'] > 65_535
        ) {
            throw new InvalidArgumentException('Warehouse allocation priority must be between 0 and 65535.');
        }
    }

    /**
     * @param array<string, mixed> $candidate
     */
    private function assertAvailableQuantity(array $candidate): void
    {
        if (
            !isset($candidate['availableQuantity'])
            || !is_int($candidate['availableQuantity'])
            || $candidate['availableQuantity'] < 0
            || $candidate['availableQuantity'] > WarehouseStockCalculator::MAX_QUANTITY
        ) {
            throw new InvalidArgumentException('Warehouse available quantity is outside the supported range.');
        }
    }

    /**
     * @param array<string, mixed> $candidate
     */
    private function assertActiveFlag(array $candidate): void
    {
        if (!array_key_exists('active', $candidate) || !is_bool($candidate['active'])) {
            throw new InvalidArgumentException('Warehouse allocation candidate must define an active flag.');
        }
    }

    /**
     * @param array{warehouseCode: string, priority: int, availableQuantity: int, active: bool} $left
     * @param array{warehouseCode: string, priority: int, availableQuantity: int, active: bool} $right
     */
    private static function compareCandidates(array $left, array $right): int
    {
        $priorityComparison = $left['priority'] <=> $right['priority'];

        if ($priorityComparison !== 0) {
            return $priorityComparison;
        }

        $stockComparison = $right['availableQuantity'] <=> $left['availableQuantity'];

        if ($stockComparison !== 0) {
            return $stockComparison;
        }

        return strcmp($left['warehouseCode'], $right['warehouseCode']);
    }
}
