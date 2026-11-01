<?php declare(strict_types=1);

namespace MedicalInventory\Command;

use MedicalInventory\Core\Content\Warehouse\WarehouseEntity;
use MedicalInventory\Service\WarehouseService;
use Shopware\Core\Framework\Context;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'medical:inventory:warehouse',
    description: 'Manage medical warehouses through the Shopware DAL',
)]
class WarehouseCrudCommand extends Command
{
    public function __construct(
        private readonly WarehouseService $warehouseService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument(
                'action',
                InputArgument::REQUIRED,
                'Action: create, list, find, activate, deactivate or delete'
            )
            ->addOption('code', null, InputOption::VALUE_REQUIRED, 'Warehouse code')
            ->addOption('name', null, InputOption::VALUE_REQUIRED, 'Warehouse name')
            ->addOption('country', null, InputOption::VALUE_REQUIRED, 'Two-letter country code');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $context = Context::createDefaultContext();
        $action = (string) $input->getArgument('action');
        $code = (string) ($input->getOption('code') ?? '');

        try {
            switch ($action) {
                case 'create':
                    $name = (string) ($input->getOption('name') ?? '');
                    $country = (string) ($input->getOption('country') ?? '');

                    $this->warehouseService->create($code, $name, $country, $context);
                    $io->success(sprintf('Warehouse "%s" created.', strtoupper(trim($code))));

                    return Command::SUCCESS;

                case 'list':
                    $rows = [];

                    foreach ($this->warehouseService->list($context)->getEntities() as $warehouse) {
                        if (!$warehouse instanceof WarehouseEntity) {
                            continue;
                        }

                        $rows[] = [
                            $warehouse->getUniqueIdentifier(),
                            $warehouse->getCode(),
                            $warehouse->getName(),
                            $warehouse->getCountryCode(),
                            $warehouse->isActive() ? 'Yes' : 'No',
                        ];
                    }

                    $io->table(['ID', 'Code', 'Name', 'Country', 'Active'], $rows);

                    return Command::SUCCESS;

                case 'find':
                    $this->requireCode($code);
                    $warehouse = $this->warehouseService->findByCode($code, $context);

                    if ($warehouse === null) {
                        $io->warning(sprintf('Warehouse "%s" was not found.', $code));

                        return Command::FAILURE;
                    }

                    $io->definitionList(
                        ['ID' => $warehouse->getUniqueIdentifier()],
                        ['Code' => $warehouse->getCode()],
                        ['Name' => $warehouse->getName()],
                        ['Country' => $warehouse->getCountryCode()],
                        ['Active' => $warehouse->isActive() ? 'Yes' : 'No'],
                    );

                    return Command::SUCCESS;

                case 'activate':
                case 'deactivate':
                    $this->requireCode($code);
                    $this->warehouseService->setActiveByCode($code, $action === 'activate', $context);
                    $io->success(sprintf(
                        'Warehouse "%s" is now %s.',
                        $code,
                        $action === 'activate' ? 'active' : 'inactive'
                    ));

                    return Command::SUCCESS;

                case 'delete':
                    $this->requireCode($code);
                    $this->warehouseService->deleteByCode($code, $context);
                    $io->success(sprintf('Warehouse "%s" deleted.', $code));

                    return Command::SUCCESS;

                default:
                    $io->error('Unknown action. Use: create, list, find, activate, deactivate or delete.');

                    return Command::INVALID;
            }
        } catch (\InvalidArgumentException | \RuntimeException $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }
    }

    private function requireCode(string $code): void
    {
        if (trim($code) === '') {
            throw new \RuntimeException('This action requires the --code option.');
        }
    }
}
