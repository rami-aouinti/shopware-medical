<?php declare(strict_types=1);

namespace MedicalInventory\ScheduledTask;

use MedicalInventory\Service\LowStockAlertService;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTask;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;

final class LowStockScanTaskHandler extends ScheduledTaskHandler
{
    public function __construct(
        \Shopware\Core\Framework\DataAbstractionLayer\EntityRepository $scheduledTaskRepository,
        LoggerInterface $exceptionLogger,
        private readonly LowStockAlertService $alertService,
    ) {
        parent::__construct($scheduledTaskRepository, $exceptionLogger);
    }

    public static function getHandledMessages(): iterable
    {
        return [LowStockScanTask::class];
    }

    public function run(): void
    {
        $this->alertService->scan();
    }
}
