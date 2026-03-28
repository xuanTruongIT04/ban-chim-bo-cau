<?php

declare(strict_types=1);

namespace App\Domain\Product\Repositories;

use App\Domain\Product\Entities\Category;

interface CategoryRepositoryInterface
{
    public function findById(int $id): ?Category;

    /** @return Category[] */
    public function all(): array;

    public function create(string $name, string $slug, ?int $parentId, ?string $description, int $sortOrder, bool $isActive): Category;

    public function update(int $id, string $name, string $slug, ?int $parentId, ?string $description, int $sortOrder, bool $isActive): Category;

    public function delete(int $id): void;

    public function hasChildren(int $id): bool;

    public function hasProducts(int $id): bool;
}
