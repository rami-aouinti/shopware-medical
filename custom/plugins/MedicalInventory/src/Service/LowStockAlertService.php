<?php declare(strict_types=1);

namespace MedicalInventory\Service;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Uuid\Uuid;

final class LowStockAlertService
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function scan(): int
    {
        return $this->connection->transactional(function (): int {
            // Lock the inventory rows in a stable order. Two scheduler workers
            // must not both observe "no OPEN alert" and insert duplicate alerts
            // for the same warehouse/product pair.
            $rows = $this->connection->fetchAllAssociative(
                <<<'SQL'
                    SELECT
                        LOWER(HEX(s.warehouse_id)) AS warehouse_id,
                        LOWER(HEX(s.product_id)) AS product_id,
                        LOWER(HEX(s.product_version_id)) AS product_version_id,
                        s.minimum_quantity,
                        s.quantity - s.reserved_quantity AS available_quantity
                    FROM medical_warehouse_stock s
                    INNER JOIN medical_warehouse w ON w.id = s.warehouse_id
                    WHERE w.is_active = 1
                      AND s.minimum_quantity > 0
                    ORDER BY s.warehouse_id, s.product_id, s.product_version_id
                    FOR UPDATE
                SQL
            );

            $openedOrUpdated = 0;
            $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s.v');

            foreach ($rows as $row) {
                $criteria = [
                    'warehouse_id' => Uuid::fromHexToBytes((string) $row['warehouse_id']),
                    'product_id' => Uuid::fromHexToBytes((string) $row['product_id']),
                    'product_version_id' => Uuid::fromHexToBytes((string) $row['product_version_id']),
                    'status' => 'OPEN',
                ];

                if ((int) $row['available_quantity'] <= (int) $row['minimum_quantity']) {
                    $existingId = $this->connection->fetchOne(
                        <<<'SQL'
                            SELECT id FROM medical_stock_alert
                            WHERE warehouse_id = :warehouse
                              AND product_id = :product
                              AND product_version_id = :version
                              AND status = 'OPEN'
                            LIMIT 1
                            FOR UPDATE
                        SQL,
                        [
                            'warehouse' => $criteria['warehouse_id'],
                            'product' => $criteria['product_id'],
                            'version' => $criteria['product_version_id'],
                        ]
                    );

                    if ($existingId === false) {
                        $this->connection->insert('medical_stock_alert', [
                            'id' => Uuid::fromHexToBytes(Uuid::randomHex()),
                            ...$criteria,
                            'threshold_quantity' => (int) $row['minimum_quantity'],
                            'available_quantity' => (int) $row['available_quantity'],
                            'opened_at' => $now,
                        ]);
                    } else {
                        $this->connection->update('medical_stock_alert', [
                            'threshold_quantity' => (int) $row['minimum_quantity'],
                            'available_quantity' => (int) $row['available_quantity'],
                        ], ['id' => $existingId]);
                    }

                    ++$openedOrUpdated;

                    continue;
                }

                $this->connection->executeStatement(
                    <<<'SQL'
                        UPDATE medical_stock_alert
                        SET status = 'RESOLVED', resolved_at = :resolvedAt
                        WHERE warehouse_id = :warehouse
                          AND product_id = :product
                          AND product_version_id = :version
                          AND status = 'OPEN'
                    SQL,
                    [
                        'resolvedAt' => $now,
                        'warehouse' => $criteria['warehouse_id'],
                        'product' => $criteria['product_id'],
                        'version' => $criteria['product_version_id'],
                    ]
                );
            }

            return $openedOrUpdated;
        });
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getOpenAlerts(): array
    {
        return $this->connection->fetchAllAssociative(
            <<<'SQL'
                SELECT
                    LOWER(HEX(a.id)) AS id,
                    w.code AS warehouse_code,
                    LOWER(HEX(a.product_id)) AS product_id,
                    a.threshold_quantity,
                    a.available_quantity,
                    a.opened_at
                FROM medical_stock_alert a
                INNER JOIN medical_warehouse w ON w.id = a.warehouse_id
                WHERE a.status = 'OPEN'
                ORDER BY a.opened_at ASC
            SQL
        );
    }
}
