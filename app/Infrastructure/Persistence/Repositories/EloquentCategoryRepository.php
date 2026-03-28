<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Product\Entities\Category;
use App\Domain\Product\Repositories\CategoryRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\Models\CategoryModel;
use App\Infrastructure\Persistence\Mappers\CategoryMapper;

final class EloquentCategoryRepository implements CategoryRepositoryInterface
{
    public function findById(int $id): ?Category
    {
        $model = CategoryModel::find($id);

        if ($model === null) {
            return null;
        }

        return CategoryMapper::toDomain($model);
    }

    /** @return Category[] */
    public function all(): array
    {
        return CategoryModel::all()
            ->map(fn (CategoryModel $model) => CategoryMapper::toDomain($model))
            ->all();
    }

    public function create(string $name, string $slug, ?int $parentId, ?string $description, int $sortOrder, bool $isActive): Category
    {
        $model = CategoryModel::create([
            'name'        => $name,
            'slug'        => $slug,
            'parent_id'   => $parentId,
            'description' => $description,
            'sort_order'  => $sortOrder,
            'is_active'   => $isActive,
        ]);

        return CategoryMapper::toDomain($model);
    }

    public function update(int $id, string $name, string $slug, ?int $parentId, ?string $description, int $sortOrder, bool $isActive): Category
    {
        $model = CategoryModel::findOrFail($id);
        $model->update([
            'name'        => $name,
            'slug'        => $slug,
            'parent_id'   => $parentId,
            'description' => $description,
            'sort_order'  => $sortOrder,
            'is_active'   => $isActive,
        ]);

        return CategoryMapper::toDomain($model->fresh());
    }

    public function delete(int $id): void
    {
        CategoryModel::findOrFail($id)->delete();
    }

    public function hasChildren(int $id): bool
    {
        return CategoryModel::where('parent_id', $id)->exists();
    }

    public function hasProducts(int $id): bool
    {
        return CategoryModel::findOrFail($id)->products()->exists();
    }
}
