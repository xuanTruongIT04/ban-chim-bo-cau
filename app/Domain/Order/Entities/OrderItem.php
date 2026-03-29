<?php

declare(strict_types=1);

namespace App\Domain\Order\Entities;

final class OrderItem
{
    public function __construct(
        public readonly int $id,
        public readonly int $orderId,
        public readonly int $productId,
        public readonly string $productName,
        public readonly int $priceVnd,
        public readonly string $quantity, // DECIMAL string
        public readonly int $subtotalVnd,
        public readonly ?string $createdAt,
        public readonly ?string $updatedAt,
    ) {}
}
