<?php declare(strict_types=1);

namespace MedicalInventory\Command;

use InvalidArgumentException;
use MedicalInventory\Service\ShipmentService;
use Shopware\Core\Framework\Context;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'medical:inventory:shipment',
    description: 'Create, list and update warehouse shipments',
)]
final class ShipmentManagementCommand extends Command
{
    public function __construct(private readonly ShipmentService $shipmentService)
    {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $this
            ->addArgument('action', InputArgument::REQUIRED, 'Action: ship, delivered or list')
            ->addOption('order', null, InputOption::VALUE_REQUIRED, 'Shopware order UUID')
            ->addOption('warehouse', null, InputOption::VALUE_REQUIRED, 'Warehouse code')
            ->addOption('product', null, InputOption::VALUE_REQUIRED, 'Shopware product UUID')
            ->addOption('quantity', null, InputOption::VALUE_REQUIRED, 'Quantity to ship')
            ->addOption('shipment', null, InputOption::VALUE_REQUIRED, 'Shipment UUID')
            ->addOption('carrier', null, InputOption::VALUE_REQUIRED, 'Carrier code')
            ->addOption('tracking', null, InputOption::VALUE_REQUIRED, 'Tracking number');
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $action = (string) $input->getArgument('action');

        try {
            if ($action === 'delivered') {
                $shipmentId = (string) ($input->getOption('shipment') ?? '');
                $this->requireValue($shipmentId, '--shipment');
                $this->shipmentService->markDelivered($shipmentId);
                $io->success('Shipment marked as delivered.');

                return Command::SUCCESS;
            }

            $orderId = (string) ($input->getOption('order') ?? '');
            $this->requireValue($orderId, '--order');

            if ($action === 'list') {
                $rows = [];

                foreach ($this->shipmentService->listByOrder($orderId) as $shipment) {
                    $rows[] = [
                        $shipment['id'],
                        $shipment['warehouse_code'],
                        $shipment['carrier_code'],
                        $shipment['tracking_number'],
                        $shipment['status'],
                        $shipment['shipped_at'],
                        $shipment['delivered_at'],
                    ];
                }

                $io->table(['Shipment', 'Warehouse', 'Carrier', 'Tracking', 'Status', 'Shipped', 'Delivered'], $rows);

                return Command::SUCCESS;
            }

            if ($action !== 'ship') {
                throw new InvalidArgumentException('Unknown action. Use ship, delivered or list.');
            }

            $warehouseCode = (string) ($input->getOption('warehouse') ?? '');
            $productId = (string) ($input->getOption('product') ?? '');
            $quantityOption = $input->getOption('quantity');
            $quantity = is_string($quantityOption) ? filter_var($quantityOption, FILTER_VALIDATE_INT) : false;

            $this->requireValue($warehouseCode, '--warehouse');
            $this->requireValue($productId, '--product');

            if ($quantity === false || $quantity <= 0) {
                throw new InvalidArgumentException('--quantity must be a positive integer.');
            }

            $shipmentId = $this->shipmentService->createShipment(
                $orderId,
                $warehouseCode,
                [['productId' => $productId, 'quantity' => $quantity]],
                Context::createDefaultContext(),
                $this->nullableOption($input, 'carrier'),
                $this->nullableOption($input, 'tracking')
            );

            $io->success(sprintf('Shipment %s created.', $shipmentId));

            return Command::SUCCESS;
        } catch (\Throwable $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }
    }

    private function nullableOption(InputInterface $input, string $name): ?string
    {
        $value = $input->getOption($name);

        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }

    private function requireValue(string $value, string $option): void
    {
        if (trim($value) === '') {
            throw new InvalidArgumentException(sprintf('This action requires the %s option.', $option));
        }
    }
}
