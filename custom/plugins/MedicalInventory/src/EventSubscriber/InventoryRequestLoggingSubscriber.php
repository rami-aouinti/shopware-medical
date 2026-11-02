<?php declare(strict_types=1);

namespace MedicalInventory\EventSubscriber;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class InventoryRequestLoggingSubscriber implements EventSubscriberInterface
{
    private const ROUTE_PREFIX = 'api.action.medical_inventory.';

    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onRequest', -10],
            KernelEvents::RESPONSE => ['onResponse', -100],
            KernelEvents::EXCEPTION => ['onException', 100],
        ];
    }

    public function onRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $route = (string) $request->attributes->get('_route', '');

        if (!str_starts_with($route, self::ROUTE_PREFIX)) {
            return;
        }

        $request->attributes->set('_medical_inventory_started_at', microtime(true));

        $this->logger->info('Medical inventory API request started.', [
            'event' => 'medical_inventory.api.request',
            'route' => $route,
            'method' => $request->getMethod(),
        ]);
    }

    public function onResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $route = (string) $request->attributes->get('_route', '');

        if (!str_starts_with($route, self::ROUTE_PREFIX)) {
            return;
        }

        $startedAt = $request->attributes->get('_medical_inventory_started_at');
        $durationMs = is_float($startedAt) ? round((microtime(true) - $startedAt) * 1000, 2) : null;
        $statusCode = $event->getResponse()->getStatusCode();
        $context = [
            'event' => 'medical_inventory.api.response',
            'route' => $route,
            'method' => $request->getMethod(),
            'status_code' => $statusCode,
            'duration_ms' => $durationMs,
        ];

        if ($statusCode >= 500) {
            $this->logger->error('Medical inventory API request failed.', $context);

            return;
        }

        $this->logger->info('Medical inventory API request completed.', $context);
    }

    public function onException(ExceptionEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $route = (string) $request->attributes->get('_route', '');

        if (!str_starts_with($route, self::ROUTE_PREFIX)) {
            return;
        }

        $exception = $event->getThrowable();
        $this->logger->error('Medical inventory API request raised an exception.', [
            'event' => 'medical_inventory.api.exception',
            'route' => $route,
            'method' => $request->getMethod(),
            'exception' => $exception::class,
        ]);
    }
}
