<?php declare(strict_types=1);

namespace MedicalInventory\Service;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;

final class StockMovementRecorder
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function record(
        string $warehouseId,
        string $productId,
        string $productVersionId,
        string $type,
        int $quantityDelta,
        int $reservedQuantityDelta,
        string $reason,
        Context $context,
        ?string $referenceType = null,
        ?string $referenceId = null,
    ): void {
        if ($quantityDelta === 0 && $reservedQuantityDelta === 0) {
            return;
        }

        $this->connection->insert('medical_stock_movement', [
            'id' => Uuid::fromHexToBytes(Uuid::randomHex()),
            'warehouse_id' => Uuid::fromHexToBytes($warehouseId),
            'product_id' => Uuid::fromHexToBytes($productId),
            'product_version_id' => Uuid::fromHexToBytes($productVersionId),
            'movement_type' => $type,
            'quantity_delta' => $quantityDelta,
            'reserved_quantity_delta' => $reservedQuantityDelta,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'reason' => mb_substr($reason, 0, 255),
            'created_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s.v'),
        ]);
    }
}
