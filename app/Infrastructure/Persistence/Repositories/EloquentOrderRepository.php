<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Order\Entities\Order;
use App\Domain\Order\Enums\DeliveryMethod;
use App\Domain\Order\Enums\OrderStatus;
use App\Domain\Order\Enums\PaymentStatus;
use App\Domain\Order\Repositories\OrderRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\Models\OrderItemModel;
use App\Infrastructure\Persistence\Eloquent\Models\OrderModel;
use App\Infrastructure\Persistence\Mappers\OrderMapper;

final class EloquentOrderRepository implements OrderRepositoryInterface
{
    /**
     * @param array<string, mixed>             $data
     * @param array<int, array<string, mixed>> $items
     */
    public function create(array $data, array $items): Order
    {
        $model = OrderModel::create($data);

        foreach ($items as $item) {
            OrderItemModel::create([
                'order_id'     => $model->id,
                'product_id'   => $item['product_id'],
                'product_name' => $item['product_name'],
                'price_vnd'    => $item['price_vnd'],
                'quantity'     => $item['quantity'],
                'subtotal_vnd' => $item['subtotal_vnd'],
            ]);
        }

        $model->load('items');

        return OrderMapper::toDomain($model);
    }

    public function findById(int $id): ?Order
    {
        $model = OrderModel::with('items')->find($id);

        if ($model === null) {
            return null;
        }

        return OrderMapper::toDomain($model);
    }

    public function updateStatus(int $id, OrderStatus $status): Order
    {
        $model = OrderModel::findOrFail($id);
        $model->update(['order_status' => $status->value]);

        return OrderMapper::toDomain($model->fresh()->load('items'));
    }

    public function updatePaymentStatus(int $id, PaymentStatus $status): Order
    {
        $model = OrderModel::findOrFail($id);
        $model->update(['payment_status' => $status->value]);

        return OrderMapper::toDomain($model->fresh()->load('items'));
    }

    public function updateDeliveryMethod(int $id, DeliveryMethod $method): Order
    {
        $model = OrderModel::findOrFail($id);
        $model->update(['delivery_method' => $method->value]);

        return OrderMapper::toDomain($model->fresh()->load('items'));
    }
}
