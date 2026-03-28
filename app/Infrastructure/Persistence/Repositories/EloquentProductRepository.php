<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Product\Entities\Product;
use App\Domain\Product\Repositories\ProductRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\Models\ProductModel;
use App\Infrastructure\Persistence\Mappers\ProductMapper;

final class EloquentProductRepository implements ProductRepositoryInterface
{
    public function findById(int $id): ?Product
    {
        $model = ProductModel::find($id);

        if ($model === null) {
            return null;
        }

        return ProductMapper::toDomain($model);
    }

    public function findByIdForUpdate(int $id): ?Product
    {
        $model = ProductModel::query()->where('id', $id)->lockForUpdate()->first();

        if ($model === null) {
            return null;
        }

        return ProductMapper::toDomain($model);
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): Product
    {
        $model = ProductModel::create($data);

        return ProductMapper::toDomain($model);
    }

    /** @param array<string, mixed> $data */
    public function update(int $id, array $data): Product
    {
        $model = ProductModel::findOrFail($id);
        $model->update($data);

        return ProductMapper::toDomain($model->fresh());
    }

    public function delete(int $id): void
    {
        ProductModel::findOrFail($id)->delete();
    }

    public function updateStock(int $id, string $newQuantity): void
    {
        ProductModel::findOrFail($id)->update(['stock_quantity' => $newQuantity]);
    }
}
