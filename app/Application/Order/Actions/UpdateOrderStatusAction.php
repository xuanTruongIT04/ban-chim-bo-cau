<?php

declare(strict_types=1);

namespace App\Application\Order\Actions;

use App\Domain\Order\Entities\Order;
use App\Domain\Order\Enums\OrderStatus;
use App\Domain\Order\Exceptions\InvalidOrderTransitionException;
use App\Domain\Order\Exceptions\OrderNotFoundException;
use App\Domain\Order\Repositories\OrderRepositoryInterface;

final class UpdateOrderStatusAction
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
    ) {}

    /**
     * Transition an order to a new status.
     *
     * Uses the Domain-layer OrderStatus enum to validate the transition (D-08, D-11, ORDR-04, ORDR-07).
     * Does NOT handle cancellation — use CancelOrderAction for that (stock restoration required).
     *
     * @throws OrderNotFoundException if the order does not exist
     * @throws InvalidOrderTransitionException if the transition is not allowed
     */
    public function handle(int $orderId, OrderStatus $newStatus): Order
    {
        $order = $this->orders->findById($orderId);

        if ($order === null) {
            throw new OrderNotFoundException($orderId);
        }

        if (! $order->orderStatus->canTransitionTo($newStatus)) {
            throw new InvalidOrderTransitionException($order->orderStatus, $newStatus);
        }

        return $this->orders->updateStatus($orderId, $newStatus);
    }
}
