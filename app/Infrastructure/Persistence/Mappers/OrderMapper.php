<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Mappers;

use App\Domain\Order\Entities\Order;
use App\Domain\Order\Entities\OrderItem;
use App\Infrastructure\Persistence\Eloquent\Models\OrderItemModel;
use App\Infrastructure\Persistence\Eloquent\Models\OrderModel;

final class OrderMapper
{
    public static function toDomain(OrderModel $model): Order
    {
        $items = $model->items->map(
            static fn (OrderItemModel $item): OrderItem => self::itemToDomain($item)
        )->all();

        return new Order(
            id: $model->id,
            customerName: $model->customer_name,
            customerPhone: $model->customer_phone,
            deliveryAddress: $model->delivery_address,
            orderStatus: $model->order_status,
            paymentMethod: $model->payment_method,
            paymentStatus: $model->payment_status,
            deliveryMethod: $model->delivery_method,
            totalAmount: (string) $model->total_amount,
            createdBy: $model->created_by !== null ? (int) $model->created_by : null,
            items: $items,
            createdAt: $model->created_at?->toIso8601String(),
            updatedAt: $model->updated_at?->toIso8601String(),
        );
    }

    public static function itemToDomain(OrderItemModel $model): OrderItem
    {
        return new OrderItem(
            id: $model->id,
            orderId: (int) $model->order_id,
            productId: (int) $model->product_id,
            productName: $model->product_name,
            priceVnd: (int) $model->price_vnd,
            quantity: (string) $model->quantity,
            subtotalVnd: (int) $model->subtotal_vnd,
            createdAt: $model->created_at?->toIso8601String(),
            updatedAt: $model->updated_at?->toIso8601String(),
        );
    }
}
