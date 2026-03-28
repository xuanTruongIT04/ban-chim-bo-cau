<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models;

use App\Domain\Product\Enums\AdjustmentType;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property AdjustmentType $adjustment_type
 * @property Carbon $created_at
 */
final class StockAdjustmentModel extends Model
{
    protected $table = 'stock_adjustments';

    public $timestamps = false;

    protected $fillable = [
        'product_id',
        'admin_user_id',
        'delta',
        'adjustment_type',
        'note',
        'stock_before',
        'stock_after',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'delta'           => 'decimal:3',
            'stock_before'    => 'decimal:3',
            'stock_after'     => 'decimal:3',
            'adjustment_type' => AdjustmentType::class,
            'created_at'      => 'datetime',
        ];
    }

    /** @return BelongsTo<ProductModel, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(ProductModel::class, 'product_id');
    }
}
