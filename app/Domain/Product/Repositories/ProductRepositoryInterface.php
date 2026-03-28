<?php

declare(strict_types=1);

namespace App\Domain\Product\Repositories;

use App\Domain\Product\Entities\Product;

interface ProductRepositoryInterface
{
    public function findById(int $id): ?Product;

    public function findByIdForUpdate(int $id): ?Product;

    /** @param array<string, mixed> $data */
    public function create(array $data): Product;

    /** @param array<string, mixed> $data */
    public function update(int $id, array $data): Product;

    public function delete(int $id): void;

    public function updateStock(int $id, string $newQuantity): void;
}
