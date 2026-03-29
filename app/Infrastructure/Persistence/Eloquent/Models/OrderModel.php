<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models;

use App\Domain\Order\Enums\DeliveryMethod;
use App\Domain\Order\Enums\OrderStatus;
use App\Domain\Order\Enums\PaymentMethod;
use App\Domain\Order\Enums\PaymentStatus;
use Database\Factories\OrderModelFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property OrderStatus $order_status
 * @property PaymentMethod $payment_method
 * @property PaymentStatus $payment_status
 * @property ?DeliveryMethod $delivery_method
 */
final class OrderModel extends Model
{
    /** @use HasFactory<OrderModelFactory> */
    use HasFactory;

    protected $table = 'orders';

    protected $fillable = [
        'customer_name',
        'customer_phone',
        'delivery_address',
        'order_status',
        'payment_method',
        'payment_status',
        'delivery_method',
        'total_amount',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'order_status'    => OrderStatus::class,
            'payment_method'  => PaymentMethod::class,
            'payment_status'  => PaymentStatus::class,
            'delivery_method' => DeliveryMethod::class,
            'total_amount'    => 'decimal:0',
        ];
    }

    protected static function newFactory(): OrderModelFactory
    {
        return OrderModelFactory::new();
    }

    /** @return HasMany<OrderItemModel, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItemModel::class, 'order_id');
    }

    /** @return BelongsTo<UserModel, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'created_by');
    }
}
