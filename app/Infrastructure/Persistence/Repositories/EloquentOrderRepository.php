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
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

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

    /**
     * @return array<string, int>
     */
    public function countByStatus(): array
    {
        $rows = OrderModel::query()
            ->selectRaw('order_status, COUNT(*) as total')
            ->groupBy('order_status')
            ->pluck('total', 'order_status');

        $result = [];
        foreach (OrderStatus::cases() as $status) {
            $result[$status->value] = (int) ($rows[$status->value] ?? 0);
        }

        return $result;
    }

    /**
     * @return LengthAwarePaginator<int, Order>
     */
    public function listWithFilters(): LengthAwarePaginator
    {
        $paginator = QueryBuilder::for(OrderModel::class)
            ->allowedFilters(
                AllowedFilter::exact('status', 'order_status'),
                AllowedFilter::callback('search', function (Builder $query, string $value): void {
                    $query->where(function (Builder $q) use ($value): void {
                        $q->where('customer_name', 'LIKE', "%{$value}%")
                          ->orWhere('customer_phone', 'LIKE', "%{$value}%");
                    });
                }),
                AllowedFilter::callback('date_from', function (Builder $query, string $value): void {
                    $query->whereDate('created_at', '>=', $value);
                }),
                AllowedFilter::callback('date_to', function (Builder $query, string $value): void {
                    $query->whereDate('created_at', '<=', $value);
                }),
            )
            ->allowedSorts(
                AllowedSort::field('created_at'),
            )
            ->defaultSort('-created_at')
            ->with('items')
            ->paginate(perPage: (int) request()->get('per_page', 20));

        /** @var LengthAwarePaginator<int, Order> $mapped */
        $mapped = $paginator->through(fn (OrderModel $model): Order => OrderMapper::toDomain($model));

        return $mapped;
    }
}
