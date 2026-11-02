<?php declare(strict_types=1);

namespace MedicalProductManagement\Transport\Console;

use MedicalProductManagement\Application\ProductCatalog\Install\InstallProductCustomFieldsHandler;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'medical:product:install-fields', description: 'Install the medical product custom fields in the Shopware product catalogue.')]
final class InstallProductCustomFieldsCommand extends Command
{
    public function __construct(private readonly InstallProductCustomFieldsHandler $handler)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->handler->handle();
        $output->writeln('<info>Medical product custom fields were installed successfully.</info>');

        return Command::SUCCESS;
    }
}