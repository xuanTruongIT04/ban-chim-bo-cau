<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Mappers;

use App\Domain\Product\Entities\Product;
use App\Infrastructure\Persistence\Eloquent\Models\ProductModel;

final class ProductMapper
{
    public static function toDomain(ProductModel $model): Product
    {
        return new Product(
            id: $model->id,
            name: $model->name,
            description: $model->description,
            priceVnd: (int) $model->price_vnd,
            unitType: $model->unit_type,
            categoryId: (int) $model->category_id,
            stockQuantity: (string) $model->stock_quantity,
            isActive: (bool) $model->is_active,
            createdAt: $model->created_at?->toIso8601String(),
            updatedAt: $model->updated_at?->toIso8601String(),
        );
    }
}
