<?php declare(strict_types=1);

namespace MedicalInventory\Command;

use DomainException;
use InvalidArgumentException;
use MedicalInventory\Core\Content\WarehouseStock\WarehouseStockEntity;
use MedicalInventory\Service\WarehouseStockService;
use RuntimeException;
use Shopware\Core\Framework\Context;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'medical:inventory:stock',
    description: 'Manage warehouse stock and reservations through the Shopware DAL',
)]
final class WarehouseStockCommand extends Command
{
    public function __construct(
        private readonly WarehouseStockService $stockService,
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $this
            ->addArgument(
                'action',
                InputArgument::REQUIRED,
                'Action: set, add, remove, reserve, release, ship, minimum, show or list'
            )
            ->addOption('warehouse', null, InputOption::VALUE_REQUIRED, 'Warehouse code')
            ->addOption('product', null, InputOption::VALUE_REQUIRED, 'Shopware product UUID')
            ->addOption('quantity', null, InputOption::VALUE_REQUIRED, 'Quantity for the operation');
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            return $this->executeAction(
                (string) $input->getArgument('action'),
                (string) ($input->getOption('warehouse') ?? ''),
                (string) ($input->getOption('product') ?? ''),
                $input,
                $io
            );
        } catch (InvalidArgumentException | DomainException | RuntimeException $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }
    }

    private function executeAction(
        string $action,
        string $warehouseCode,
        string $productId,
        InputInterface $input,
        SymfonyStyle $io,
    ): int {
        $this->requireValue($warehouseCode, '--warehouse');

        return match ($action) {
            'list' => $this->listStock($warehouseCode, $io),
            'minimum' => $this->updateMinimum($warehouseCode, $productId, $input, $io),
            'show' => $this->showStock($warehouseCode, $productId, $io),
            'set', 'add', 'remove', 'reserve', 'release', 'ship' => $this->performMutation(
                $action,
                $warehouseCode,
                $productId,
                $input,
                $io
            ),
            default => throw new InvalidArgumentException(
                'Unknown action. Use set, add, remove, reserve, release, ship, minimum, show or list.'
            ),
        };
    }

    private function listStock(string $warehouseCode, SymfonyStyle $io): int
    {
        $rows = [];

        $stocks = $this->stockService->listByWarehouse($warehouseCode, Context::createDefaultContext())->getEntities();

        foreach ($stocks as $stock) {
            if (!$stock instanceof WarehouseStockEntity) {
                continue;
            }

            $rows[] = [
                $stock->getProductId(),
                $stock->getQuantity(),
                $stock->getReservedQuantity(),
                $stock->getAvailableQuantity(),
                $stock->getMinimumQuantity(),
            ];
        }

        $io->table(['Product ID', 'Quantity', 'Reserved', 'Available', 'Minimum'], $rows);

        return Command::SUCCESS;
    }

    private function updateMinimum(
        string $warehouseCode,
        string $productId,
        InputInterface $input,
        SymfonyStyle $io,
    ): int {
        $this->requireValue($productId, '--product');
        $quantity = $this->readQuantity($input, 'The minimum action requires an integer --quantity option.');
        $this->stockService->setMinimumQuantity(
            $warehouseCode,
            $productId,
            $quantity,
            Context::createDefaultContext()
        );
        $io->success('Minimum stock threshold updated.');

        return Command::SUCCESS;
    }

    private function showStock(string $warehouseCode, string $productId, SymfonyStyle $io): int
    {
        $this->requireValue($productId, '--product');
        $stock = $this->stockService->getStock($warehouseCode, $productId, Context::createDefaultContext());

        if (!$stock instanceof WarehouseStockEntity) {
            $io->warning('No stock row exists for this warehouse and product.');

            return Command::FAILURE;
        }

        $this->renderStock($io, $stock);

        return Command::SUCCESS;
    }

    private function performMutation(
        string $action,
        string $warehouseCode,
        string $productId,
        InputInterface $input,
        SymfonyStyle $io,
    ): int {
        $this->requireValue($productId, '--product');
        $quantity = $this->readQuantity($input, 'This action requires an integer --quantity option.');
        $context = Context::createDefaultContext();

        $stock = match ($action) {
            'set' => $this->stockService->setStock($warehouseCode, $productId, $quantity, $context),
            'add' => $this->stockService->addStock($warehouseCode, $productId, $quantity, $context),
            'remove' => $this->stockService->removeStock($warehouseCode, $productId, $quantity, $context),
            'reserve' => $this->stockService->reserveStock($warehouseCode, $productId, $quantity, $context),
            'release' => $this->stockService->releaseReservation($warehouseCode, $productId, $quantity, $context),
            'ship' => $this->stockService->shipReservedStock($warehouseCode, $productId, $quantity, $context),
            default => throw new InvalidArgumentException(sprintf('Unsupported stock action "%s".', $action)),
        };

        $io->success(sprintf('Stock operation "%s" completed.', $action));
        $this->renderStock($io, $stock);

        return Command::SUCCESS;
    }

    private function readQuantity(InputInterface $input, string $errorMessage): int
    {
        $option = $input->getOption('quantity');
        $quantity = is_string($option) ? filter_var($option, FILTER_VALIDATE_INT) : false;

        if ($quantity === false) {
            throw new InvalidArgumentException($errorMessage);
        }

        return $quantity;
    }

    private function renderStock(SymfonyStyle $io, WarehouseStockEntity $stock): void
    {
        $io->definitionList(
            ['Product ID' => $stock->getProductId()],
            ['Quantity' => $stock->getQuantity()],
            ['Reserved' => $stock->getReservedQuantity()],
            ['Available' => $stock->getAvailableQuantity()],
            ['Minimum threshold' => $stock->getMinimumQuantity()],
        );
    }

    private function requireValue(string $value, string $option): void
    {
        if (trim($value) === '') {
            throw new RuntimeException(sprintf('This action requires the %s option.', $option));
        }
    }
}
