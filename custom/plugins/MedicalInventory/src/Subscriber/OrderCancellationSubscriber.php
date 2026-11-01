<?php declare(strict_types=1);

namespace MedicalInventory\Subscriber;

use Doctrine\DBAL\Connection;
use MedicalInventory\Service\OrderFulfillmentService;
use Shopware\Core\Checkout\Order\OrderStates;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\StateMachine\Event\StateMachineTransitionEvent;

final class OrderCancellationSubscriber
{
    public function __construct(
        private readonly Connection $connection,
        private readonly OrderFulfillmentService $fulfillmentService,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [StateMachineTransitionEvent::class => 'onStateMachineTransition'];
    }

    public function onStateMachineTransition(StateMachineTransitionEvent $event): void
    {
        if (
            $event->getEntityName() !== 'order'
            || $event->getToPlace()->getTechnicalName() !== OrderStates::STATE_CANCELLED
            || !Uuid::isValid($event->getEntityId())
        ) {
            return;
        }

        $this->connection->transactional(function () use ($event): void {
            $this->fulfillmentService->releaseOrder($event->getEntityId(), $event->getContext());
        });
    }
}
