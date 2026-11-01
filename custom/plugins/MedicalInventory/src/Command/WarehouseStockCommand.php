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

    protected function configure(): void
    {
        $this
            ->addArgument(
                'action',
                InputArgument::REQUIRED,
                'Action: set, add, remove, reserve, release, ship, show or list'
            )
            ->addOption('warehouse', null, InputOption::VALUE_REQUIRED, 'Warehouse code')
            ->addOption('product', null, InputOption::VALUE_REQUIRED, 'Shopware product UUID')
            ->addOption('quantity', null, InputOption::VALUE_REQUIRED, 'Quantity for the operation');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $context = Context::createDefaultContext();
        $action = (string) $input->getArgument('action');
        $warehouseCode = (string) ($input->getOption('warehouse') ?? '');
        $productId = (string) ($input->getOption('product') ?? '');

        try {
            $this->requireValue($warehouseCode, '--warehouse');

            if ($action === 'list') {
                $rows = [];

                foreach ($this->stockService->listByWarehouse($warehouseCode, $context)->getEntities() as $stock) {
                    if (!$stock instanceof WarehouseStockEntity) {
                        continue;
                    }

                    $rows[] = [
                        $stock->getProductId(),
                        $stock->getQuantity(),
                        $stock->getReservedQuantity(),
                        $stock->getAvailableQuantity(),
                    ];
                }

                $io->table(['Product ID', 'Quantity', 'Reserved', 'Available'], $rows);

                return Command::SUCCESS;
            }

            $this->requireValue($productId, '--product');

            if ($action === 'show') {
                $stock = $this->stockService->getStock($warehouseCode, $productId, $context);

                if ($stock === null) {
                    $io->warning('No stock row exists for this warehouse and product.');

                    return Command::FAILURE;
                }

                $this->renderStock($io, $stock);

                return Command::SUCCESS;
            }

            $quantityOption = $input->getOption('quantity');
            $validatedQuantity = is_string($quantityOption)
                ? filter_var($quantityOption, FILTER_VALIDATE_INT)
                : false;

            if ($validatedQuantity === false) {
                throw new InvalidArgumentException('This action requires an integer --quantity option.');
            }

            $quantity = $validatedQuantity;

            $stock = match ($action) {
                'set' => $this->stockService->setStock($warehouseCode, $productId, $quantity, $context),
                'add' => $this->stockService->addStock($warehouseCode, $productId, $quantity, $context),
                'remove' => $this->stockService->removeStock($warehouseCode, $productId, $quantity, $context),
                'reserve' => $this->stockService->reserveStock($warehouseCode, $productId, $quantity, $context),
                'release' => $this->stockService->releaseReservation($warehouseCode, $productId, $quantity, $context),
                'ship' => $this->stockService->shipReservedStock($warehouseCode, $productId, $quantity, $context),
                default => throw new InvalidArgumentException(
                    'Unknown action. Use set, add, remove, reserve, release, ship, show or list.'
                ),
            };

            $io->success(sprintf('Stock operation "%s" completed.', $action));
            $this->renderStock($io, $stock);

            return Command::SUCCESS;
        } catch (InvalidArgumentException | DomainException | RuntimeException $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }
    }

    private function renderStock(SymfonyStyle $io, WarehouseStockEntity $stock): void
    {
        $io->definitionList(
            ['Product ID' => $stock->getProductId()],
            ['Quantity' => $stock->getQuantity()],
            ['Reserved' => $stock->getReservedQuantity()],
            ['Available' => $stock->getAvailableQuantity()],
        );
    }

    private function requireValue(string $value, string $option): void
    {
        if (trim($value) === '') {
            throw new RuntimeException(sprintf('This action requires the %s option.', $option));
        }
    }
}
