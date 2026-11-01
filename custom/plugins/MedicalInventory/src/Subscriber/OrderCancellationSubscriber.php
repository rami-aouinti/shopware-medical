<?php declare(strict_types=1);

namespace MedicalInventory\Subscriber;

use Doctrine\DBAL\Connection;
use MedicalInventory\Service\OrderFulfillmentService;
use Shopware\Core\Checkout\Order\OrderStates;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\StateMachine\Event\StateMachineTransitionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class OrderCancellationSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly Connection $connection,
        private readonly OrderFulfillmentService $fulfillmentService,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [StateMachineTransitionEvent::class => ['onStateMachineTransition', -100]];
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

        $event->getContext()->scope(
            Context::SYSTEM_SCOPE,
            function (Context $systemContext) use ($event): void {
                $this->connection->transactional(function () use ($event, $systemContext): void {
                    $this->fulfillmentService->releaseOrder($event->getEntityId(), $systemContext);
                });
            }
        );
    }
}
