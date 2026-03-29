<?php

declare(strict_types=1);

namespace App\Domain\Order\Entities;

final class CartItem
{
    public function __construct(
        public readonly int $id,
        public readonly int $cartId,
        public readonly int $productId,
        public readonly string $quantity, // DECIMAL string
        public readonly ?string $createdAt,
        public readonly ?string $updatedAt,
    ) {}
}
