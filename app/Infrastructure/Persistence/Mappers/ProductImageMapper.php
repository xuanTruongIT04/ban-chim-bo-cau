<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Mappers;

use App\Domain\Product\Entities\ProductImage;
use App\Infrastructure\Persistence\Eloquent\Models\ProductImageModel;

final class ProductImageMapper
{
    public static function toDomain(ProductImageModel $model): ProductImage
    {
        return new ProductImage(
            id: $model->id,
            productId: (int) $model->product_id,
            path: $model->path,
            thumbnailPath: $model->thumbnail_path,
            isPrimary: (bool) $model->is_primary,
            sortOrder: (int) $model->sort_order,
        );
    }
}
