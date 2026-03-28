<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Mappers;

use App\Domain\Product\Entities\Category;
use App\Infrastructure\Persistence\Eloquent\Models\CategoryModel;

final class CategoryMapper
{
    public static function toDomain(CategoryModel $model): Category
    {
        return new Category(
            id: $model->id,
            name: $model->name,
            slug: $model->slug,
            parentId: $model->parent_id,
            description: $model->description,
            sortOrder: (int) $model->sort_order,
            isActive: (bool) $model->is_active,
        );
    }
}
