<?php declare(strict_types=1);

namespace MedicalInventory\Command;

use MedicalInventory\Service\LowStockAlertService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'medical:inventory:alerts:scan',
    description: 'Scan warehouse stock and open or resolve low-stock alerts',
)]
final class LowStockAlertScanCommand extends Command
{
    public function __construct(private readonly LowStockAlertService $alertService)
    {
        parent::__construct();
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $count = $this->alertService->scan();
        $io->success(sprintf('Low-stock scan completed. %d alerts are currently low or updated.', $count));

        return Command::SUCCESS;
    }
}
