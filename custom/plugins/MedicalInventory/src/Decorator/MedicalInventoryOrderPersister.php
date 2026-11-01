<?php declare(strict_types=1);

namespace MedicalInventory\Decorator;

use Doctrine\DBAL\Connection;
use MedicalInventory\Service\OrderFulfillmentService;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\Order\OrderPersisterInterface;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

final class MedicalInventoryOrderPersister implements OrderPersisterInterface
{
    public function __construct(
        private readonly OrderPersisterInterface $decorated,
        private readonly Connection $connection,
        private readonly OrderFulfillmentService $fulfillmentService,
    ) {
    }

    public function persist(Cart $cart, SalesChannelContext $context): string
    {
        return $this->connection->transactional(function () use ($cart, $context): string {
            $orderId = $this->decorated->persist($cart, $context);
            $this->fulfillmentService->reserveOrder($orderId, $cart, $context->getContext());

            return $orderId;
        });
    }
}
