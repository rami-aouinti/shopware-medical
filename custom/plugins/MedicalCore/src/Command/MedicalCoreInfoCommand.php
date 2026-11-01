<?php declare(strict_types=1);

namespace MedicalCore\Command;

use MedicalCore\Service\ProjectInfoProvider;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'medical:core:info',
    description: 'Displays the MedicalSupply project configuration.'
)]
final class MedicalCoreInfoCommand extends Command
{
    public function __construct(
        private readonly ProjectInfoProvider $projectInfoProvider
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('MedicalSupply B2B Commerce');

        foreach ($this->projectInfoProvider->getProjectInfo() as $key => $value) {
            $io->definitionList([$key => $value]);
        }

        return Command::SUCCESS;
    }
}
