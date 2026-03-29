<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Mappers;

use App\Domain\Order\Entities\Cart;
use App\Domain\Order\Entities\CartItem;
use App\Infrastructure\Persistence\Eloquent\Models\CartItemModel;
use App\Infrastructure\Persistence\Eloquent\Models\CartModel;

final class CartMapper
{
    public static function toDomain(CartModel $model): Cart
    {
        $items = $model->items->map(
            static fn (CartItemModel $item): CartItem => self::itemToDomain($item)
        )->all();

        return new Cart(
            id: $model->id,
            token: $model->token,
            expiresAt: $model->expires_at->toIso8601String(),
            items: $items,
            createdAt: $model->created_at?->toIso8601String(),
            updatedAt: $model->updated_at?->toIso8601String(),
        );
    }

    public static function itemToDomain(CartItemModel $model): CartItem
    {
        return new CartItem(
            id: $model->id,
            cartId: (int) $model->cart_id,
            productId: (int) $model->product_id,
            quantity: (string) $model->quantity,
            createdAt: $model->created_at?->toIso8601String(),
            updatedAt: $model->updated_at?->toIso8601String(),
        );
    }
}
