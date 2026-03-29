<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Order\Entities\Cart;
use App\Domain\Order\Entities\CartItem;
use App\Domain\Order\Repositories\CartRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\Models\CartItemModel;
use App\Infrastructure\Persistence\Eloquent\Models\CartModel;
use App\Infrastructure\Persistence\Mappers\CartMapper;
use Illuminate\Support\Str;

final class EloquentCartRepository implements CartRepositoryInterface
{
    public function create(): Cart
    {
        $model = CartModel::create([
            'token'      => (string) Str::uuid(),
            'expires_at' => now()->addDays(7),
        ]);

        $model->load('items');

        return CartMapper::toDomain($model);
    }

    public function findByToken(string $token): ?Cart
    {
        $model = CartModel::query()
            ->where('token', $token)
            ->where('expires_at', '>', now())
            ->with('items')
            ->first();

        if ($model === null) {
            return null;
        }

        return CartMapper::toDomain($model);
    }

    public function addItem(int $cartId, int $productId, string $quantity): CartItem
    {
        $item = CartItemModel::create([
            'cart_id'    => $cartId,
            'product_id' => $productId,
            'quantity'   => $quantity,
        ]);

        $this->refreshExpiry($cartId);

        return CartMapper::itemToDomain($item);
    }

    public function updateItemQuantity(int $cartItemId, string $quantity): CartItem
    {
        $item = CartItemModel::findOrFail($cartItemId);
        $item->update(['quantity' => $quantity]);

        $this->refreshExpiry((int) $item->cart_id);

        return CartMapper::itemToDomain($item->fresh());
    }

    public function removeItem(int $cartItemId): void
    {
        CartItemModel::findOrFail($cartItemId)->delete();
    }

    public function findItemByCartAndProduct(int $cartId, int $productId): ?CartItem
    {
        $item = CartItemModel::query()
            ->where('cart_id', $cartId)
            ->where('product_id', $productId)
            ->first();

        if ($item === null) {
            return null;
        }

        return CartMapper::itemToDomain($item);
    }

    public function refreshExpiry(int $cartId): void
    {
        CartModel::where('id', $cartId)->update(['expires_at' => now()->addDays(7)]);
    }

    public function deleteExpired(): int
    {
        return CartModel::query()
            ->where('expires_at', '<', now())
            ->delete();
    }

    public function delete(int $cartId): void
    {
        CartModel::findOrFail($cartId)->delete();
    }
}
