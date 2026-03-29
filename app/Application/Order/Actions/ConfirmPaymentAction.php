<?php

declare(strict_types=1);

namespace App\Application\Order\Actions;

use App\Domain\Order\Entities\Order;
use App\Domain\Order\Enums\OrderStatus;
use App\Domain\Order\Enums\PaymentStatus;
use App\Domain\Order\Exceptions\InvalidOrderTransitionException;
use App\Domain\Order\Exceptions\OrderNotFoundException;
use App\Domain\Order\Repositories\OrderRepositoryInterface;

final class ConfirmPaymentAction
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
    ) {}

    /**
     * Confirm payment for an order, setting payment_status to da_thanh_toan.
     *
     * Per PAYM-04: admin can confirm payment.
     * Idempotent: if already da_thanh_toan, returns order unchanged.
     * Rejects if order is cancelled.
     *
     * @throws OrderNotFoundException if the order does not exist
     * @throws InvalidOrderTransitionException if the order is cancelled
     */
    public function handle(int $orderId): Order
    {
        $order = $this->orders->findById($orderId);

        if ($order === null) {
            throw new OrderNotFoundException($orderId);
        }

        // Idempotent: already confirmed, return as-is
        if ($order->paymentStatus === PaymentStatus::DaThanhToan) {
            return $order;
        }

        // Cannot confirm payment on a cancelled order
        if ($order->orderStatus === OrderStatus::Huy) {
            throw new InvalidOrderTransitionException(
                $order->orderStatus,
                OrderStatus::Huy
            );
        }

        return $this->orders->updatePaymentStatus($orderId, PaymentStatus::DaThanhToan);
    }
}
