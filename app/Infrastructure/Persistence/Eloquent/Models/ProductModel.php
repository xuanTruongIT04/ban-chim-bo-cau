<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models;

use App\Domain\Product\Enums\UnitType;
use Database\Factories\ProductModelFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property UnitType $unit_type
 */
final class ProductModel extends Model
{
    /** @use HasFactory<ProductModelFactory> */
    use HasFactory;

    protected $table = 'products';

    protected $fillable = [
        'category_id',
        'name',
        'description',
        'price_vnd',
        'unit_type',
        'stock_quantity',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'stock_quantity' => 'decimal:3',
            'price_vnd'      => 'integer',
            'is_active'      => 'boolean',
            'unit_type'      => UnitType::class,
        ];
    }

    protected static function newFactory(): ProductModelFactory
    {
        return ProductModelFactory::new();
    }

    /** @return BelongsTo<CategoryModel, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(CategoryModel::class, 'category_id');
    }

    /** @return HasMany<ProductImageModel, $this> */
    public function images(): HasMany
    {
        return $this->hasMany(ProductImageModel::class, 'product_id');
    }

    /** @return HasMany<StockAdjustmentModel, $this> */
    public function stockAdjustments(): HasMany
    {
        return $this->hasMany(StockAdjustmentModel::class, 'product_id');
    }
}
