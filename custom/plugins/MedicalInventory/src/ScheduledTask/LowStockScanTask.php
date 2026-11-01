<?php declare(strict_types=1);

namespace MedicalInventory\ScheduledTask;

use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTask;

final class LowStockScanTask extends ScheduledTask
{
    #[\Override]
    public static function getTaskName(): string
    {
        return 'medical_inventory.low_stock_scan';
    }

    #[\Override]
    public static function getDefaultInterval(): int
    {
        return 3600;
    }
}
