<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class OrderItemModel extends Model
{
    protected $table = 'order_items';

    protected $fillable = [
        'order_id',
        'product_id',
        'product_name',
        'price_vnd',
        'quantity',
        'subtotal_vnd',
    ];

    protected function casts(): array
    {
        return [
            'quantity'     => 'decimal:3',
            'price_vnd'    => 'integer',
            'subtotal_vnd' => 'integer',
        ];
    }

    /** @return BelongsTo<OrderModel, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(OrderModel::class, 'order_id');
    }

    /** @return BelongsTo<ProductModel, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(ProductModel::class, 'product_id');
    }
}
