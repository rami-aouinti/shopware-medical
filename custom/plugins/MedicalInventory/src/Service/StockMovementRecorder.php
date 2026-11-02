<?php declare(strict_types=1);

namespace MedicalInventory\Service;

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Uuid\Uuid;

final class StockMovementRecorder
{
    public function __construct(\n        private readonly Connection $connection,\n        private readonly LoggerInterface $logger,\n    )
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
        ]);\n\n        $this->logger->info('Medical inventory stock movement recorded.', [\n            'event' => 'medical_inventory.stock.movement',\n            'warehouse_id' => $warehouseId,\n            'product_id' => $productId,\n            'movement_type' => $type,\n            'quantity_delta' => $quantityDelta,\n            'reserved_quantity_delta' => $reservedQuantityDelta,\n            'reference_type' => $referenceType,\n        ]);\n    }\n}
