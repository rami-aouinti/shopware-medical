<?php declare(strict_types=1);

namespace MedicalInventory\Controller;

use MedicalInventory\Monitoring\InventoryMonitoringService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route(defaults: ['_routeScope' => ['api']])]
final class MedicalInventoryMonitoringController
{
    public function __construct(
        private readonly InventoryMonitoringService $monitoringService,
        private readonly LoggerInterface $logger,
    ) {
    }

    #[Route(
        path: '/api/_action/medical-inventory/monitoring',
        name: 'api.action.medical_inventory.monitoring',
        methods: ['GET'],
        defaults: ['_acl' => ['product:read']]
    )]
    public function metrics(): JsonResponse
    {
        try {
            $metrics = $this->monitoringService->collectMetrics();

            $this->logger->info('Medical inventory monitoring metrics collected.', [
                'event' => 'medical_inventory.monitoring.collected',
                'metrics' => $metrics,
            ]);

            return new JsonResponse([
                'status' => 'ok',
                'component' => 'medical-inventory',
                'generatedAt' => (new \DateTimeImmutable())->format(DATE_ATOM),
                'metrics' => $metrics,
            ]);
        } catch (\Throwable $exception) {
            $this->logger->error('Medical inventory monitoring collection failed.', [
                'event' => 'medical_inventory.monitoring.failed',
                'exception' => $exception::class,
            ]);

            throw $exception;
        }
    }
}
