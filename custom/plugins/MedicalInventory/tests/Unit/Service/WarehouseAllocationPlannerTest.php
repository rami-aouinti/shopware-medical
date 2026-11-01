<?php declare(strict_types=1);

namespace MedicalInventory\Tests\Unit\Service;

use InvalidArgumentException;
use MedicalInventory\Service\InsufficientWarehouseStockException;
use MedicalInventory\Service\WarehouseAllocationPlanner;
use PHPUnit\Framework\TestCase;

final class WarehouseAllocationPlannerTest extends TestCase
{
    private WarehouseAllocationPlanner $planner;

    #[\Override]
    protected function setUp(): void
    {
        $this->planner = new WarehouseAllocationPlanner();
    }

    public function testItPrefersOneWarehouseThatCanFulfilTheWholeRequest(): void
    {
        $result = $this->planner->plan(20, [
            $this->candidate('DE-KOELN', 1, 15),
            $this->candidate('DE-BERLIN', 2, 25),
        ]);

        self::assertSame([
            ['warehouseCode' => 'DE-BERLIN', 'priority' => 2, 'quantity' => 20],
        ], $result);
    }

    public function testItChoosesTheHighestPriorityWarehouseWhenSeveralCanFulfilTheRequest(): void
    {
        $result = $this->planner->plan(10, [
            $this->candidate('DE-BERLIN', 2, 30),
            $this->candidate('DE-KOELN', 1, 12),
        ]);

        self::assertSame([
            ['warehouseCode' => 'DE-KOELN', 'priority' => 1, 'quantity' => 10],
        ], $result);
    }

    public function testItUsesTheLargestStockWhenPrioritiesAreEqual(): void
    {
        $result = $this->planner->plan(10, [
            $this->candidate('DE-KOELN', 1, 12),
            $this->candidate('DE-BERLIN', 1, 20),
        ]);

        self::assertSame([
            ['warehouseCode' => 'DE-BERLIN', 'priority' => 1, 'quantity' => 10],
        ], $result);
    }

    public function testItUsesWarehouseCodeAsDeterministicFinalTieBreaker(): void
    {
        $result = $this->planner->plan(10, [
            $this->candidate('DE-KOELN', 1, 12),
            $this->candidate('DE-BERLIN', 1, 12),
        ]);

        self::assertSame('DE-BERLIN', $result[0]['warehouseCode']);
    }

    public function testItSplitsTheRequestByPriorityAndAvailableStockWhenNoWarehouseCanFulfilItAlone(): void
    {
        $result = $this->planner->plan(20, [
            $this->candidate('DE-HAMBURG', 3, 8),
            $this->candidate('DE-BERLIN', 2, 12),
            $this->candidate('DE-KOELN', 1, 15),
        ]);

        self::assertSame([
            ['warehouseCode' => 'DE-KOELN', 'priority' => 1, 'quantity' => 15],
            ['warehouseCode' => 'DE-BERLIN', 'priority' => 2, 'quantity' => 5],
        ], $result);
    }

    public function testItSkipsInactiveWarehouses(): void
    {
        $result = $this->planner->plan(5, [
            $this->candidate('DE-KOELN', 1, 20, false),
            $this->candidate('DE-BERLIN', 2, 10),
        ]);

        self::assertSame([
            ['warehouseCode' => 'DE-BERLIN', 'priority' => 2, 'quantity' => 5],
        ], $result);
    }

    public function testItSkipsWarehousesWithoutAvailableStock(): void
    {
        $result = $this->planner->plan(5, [
            $this->candidate('DE-KOELN', 1, 0),
            $this->candidate('DE-BERLIN', 2, 10),
        ]);

        self::assertSame('DE-BERLIN', $result[0]['warehouseCode']);
    }

    public function testItRejectsTheRequestWhenCombinedStockIsInsufficient(): void
    {
        try {
            $this->planner->plan(20, [
                $this->candidate('DE-KOELN', 1, 10),
                $this->candidate('DE-BERLIN', 2, 5),
            ]);
            self::fail('An insufficient stock exception was expected.');
        } catch (InsufficientWarehouseStockException $exception) {
            self::assertSame(20, $exception->getRequestedQuantity());
            self::assertSame(15, $exception->getAvailableQuantity());
        }
    }

    public function testItRejectsZeroOrNegativeRequestedQuantities(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->planner->plan(0, []);
    }

    public function testItRejectsDuplicateWarehouseCandidates(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->planner->plan(5, [
            $this->candidate('DE-KOELN', 1, 10),
            $this->candidate('de-koeln', 2, 10),
        ]);
    }

    public function testItRejectsInvalidCandidateValues(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->planner->plan(5, [[
            'warehouseCode' => 'DE-KOELN',
            'priority' => -1,
            'availableQuantity' => 10,
            'active' => true,
        ]]);
    }

    /**
     * @return array{warehouseCode: string, priority: int, availableQuantity: int, active: bool}
     */
    private function candidate(
        string $code,
        int $priority,
        int $availableQuantity,
        bool $active = true,
    ): array {
        return [
            'warehouseCode' => $code,
            'priority' => $priority,
            'availableQuantity' => $availableQuantity,
            'active' => $active,
        ];
    }
}
