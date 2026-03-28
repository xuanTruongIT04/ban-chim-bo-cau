<?php

declare(strict_types=1);

namespace App\Application\Product\Actions;

use App\Domain\Product\Entities\Product;
use App\Domain\Product\Exceptions\CategoryNotFoundException;
use App\Domain\Product\Exceptions\ProductNotFoundException;
use App\Domain\Product\Repositories\CategoryRepositoryInterface;
use App\Domain\Product\Repositories\ProductRepositoryInterface;

final class UpdateProductAction
{
    public function __construct(
        private readonly ProductRepositoryInterface $products,
        private readonly CategoryRepositoryInterface $categories,
    ) {}

    public function handle(
        int $id,
        string $name,
        ?string $description,
        int $priceVnd,
        string $unitType,
        int $categoryId,
        bool $isActive,
    ): Product {
        $existing = $this->products->findById($id);

        if ($existing === null) {
            throw new ProductNotFoundException($id);
        }

        $category = $this->categories->findById($categoryId);

        if ($category === null) {
            throw new CategoryNotFoundException($categoryId);
        }

        return $this->products->update($id, [
            'name'        => $name,
            'description' => $description,
            'price_vnd'   => $priceVnd,
            'unit_type'   => $unitType,
            'category_id' => $categoryId,
            'is_active'   => $isActive,
        ]);
    }
}
