<?php

declare(strict_types=1);

namespace App\Domain\Order\Entities;

use App\Domain\Order\Enums\DeliveryMethod;
use App\Domain\Order\Enums\OrderStatus;
use App\Domain\Order\Enums\PaymentMethod;
use App\Domain\Order\Enums\PaymentStatus;

final class Order
{
    public function __construct(
        public readonly int $id,
        public readonly string $customerName,
        public readonly string $customerPhone,
        public readonly string $deliveryAddress,
        public readonly OrderStatus $orderStatus,
        public readonly PaymentMethod $paymentMethod,
        public readonly PaymentStatus $paymentStatus,
        public readonly ?DeliveryMethod $deliveryMethod,
        public readonly string $totalAmount, // DECIMAL string
        public readonly ?int $createdBy,
        /** @var array<OrderItem> */
        public readonly array $items,
        public readonly ?string $createdAt,
        public readonly ?string $updatedAt,
    ) {}
}
