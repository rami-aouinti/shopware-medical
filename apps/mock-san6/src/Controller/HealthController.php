<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class HealthController
{
    #[Route('/api/health', name: 'api_health', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        return new JsonResponse([
            'application' => 'Mock SAN6 ERP',
            'status' => 'healthy',
            'version' => '1.0.0',
            'timestamp' => (new \DateTimeImmutable())->format(DATE_ATOM),
        ]);
    }
}
