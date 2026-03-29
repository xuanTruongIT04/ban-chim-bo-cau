<?php

declare(strict_types=1);

namespace App\Domain\Order\Repositories;

use App\Domain\Order\Entities\Cart;
use App\Domain\Order\Entities\CartItem;

interface CartRepositoryInterface
{
    public function create(): Cart;

    public function findByToken(string $token): ?Cart;

    public function addItem(int $cartId, int $productId, string $quantity): CartItem;

    public function updateItemQuantity(int $cartItemId, string $quantity): CartItem;

    public function removeItem(int $cartItemId): void;

    public function findItemByCartAndProduct(int $cartId, int $productId): ?CartItem;

    public function refreshExpiry(int $cartId): void;

    public function deleteExpired(): int;

    public function delete(int $cartId): void;
}
