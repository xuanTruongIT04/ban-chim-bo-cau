<?php

declare(strict_types=1);

namespace App\Domain\Order\Entities;

final class Cart
{
    public function __construct(
        public readonly int $id,
        public readonly string $token,
        public readonly string $expiresAt,
        /** @var array<CartItem> */
        public readonly array $items,
        public readonly ?string $createdAt,
        public readonly ?string $updatedAt,
    ) {}
}
