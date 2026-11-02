<?php declare(strict_types=1);

namespace MedicalInventory\ScheduledTask;

use MedicalInventory\Service\LowStockAlertService;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTask;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;

final class LowStockScanTaskHandler extends ScheduledTaskHandler
{
    public function __construct(
        EntityRepository $scheduledTaskRepository,
        LoggerInterface $exceptionLogger,
        private readonly LowStockAlertService $alertService,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct($scheduledTaskRepository, $exceptionLogger);
    }

    public static function getHandledMessages(): iterable
    {
        return [LowStockScanTask::class];
    }

    #[\Override]
    public function run(): void
    {
        $startedAt = microtime(true);
        $this->logger->info('Medical inventory low-stock scan started.', [
            'event' => 'medical_inventory.alert_scan.started',
        ]);

        try {
            $count = $this->alertService->scan();
            $this->logger->info('Medical inventory low-stock scan completed.', [
                'event' => 'medical_inventory.alert_scan.completed',
                'alerts_opened_or_updated' => $count,
                'duration_ms' => round((microtime(true) - $startedAt) * 1000, 2),
            ]);
        } catch (\Throwable $exception) {
            $this->logger->error('Medical inventory low-stock scan failed.', [
                'event' => 'medical_inventory.alert_scan.failed',
                'exception' => $exception::class,
                'duration_ms' => round((microtime(true) - $startedAt) * 1000, 2),
            ]);

            throw $exception;
        }
    }
}
