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
        if ($requestedQuantity <= 0 || $requestedQuantity > WarehouseStockCalculator::MAX_QUANTITY) {
            throw new InvalidArgumentException('Requested quantity must be between 1 and 4294967295.');
        }

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

        foreach ($candidates as $candidate) {
            if ($candidate['availableQuantity'] >= $requestedQuantity) {
                return [[
                    'warehouseCode' => $candidate['warehouseCode'],
                    'priority' => $candidate['priority'],
                    'quantity' => $requestedQuantity,
                ]];
            }
        }

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
        if (
            !isset($candidate['warehouseCode'])
            || !is_string($candidate['warehouseCode'])
            || trim($candidate['warehouseCode']) === ''
            || !isset($candidate['priority'])
            || !is_int($candidate['priority'])
            || $candidate['priority'] < 0
            || $candidate['priority'] > 65_535
            || !isset($candidate['availableQuantity'])
            || !is_int($candidate['availableQuantity'])
            || $candidate['availableQuantity'] < 0
            || $candidate['availableQuantity'] > WarehouseStockCalculator::MAX_QUANTITY
            || !array_key_exists('active', $candidate)
            || !is_bool($candidate['active'])
        ) {
            throw new InvalidArgumentException('Warehouse allocation candidate contains invalid data.');
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
