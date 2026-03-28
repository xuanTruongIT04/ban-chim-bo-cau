<?php

declare(strict_types=1);

namespace App\Application\Product\Actions;

use App\Domain\Product\Entities\Product;
use App\Domain\Product\Exceptions\CategoryNotFoundException;
use App\Domain\Product\Repositories\CategoryRepositoryInterface;
use App\Domain\Product\Repositories\ProductRepositoryInterface;

final class CreateProductAction
{
    public function __construct(
        private readonly ProductRepositoryInterface $products,
        private readonly CategoryRepositoryInterface $categories,
    ) {}

    public function handle(
        string $name,
        ?string $description,
        int $priceVnd,
        string $unitType,
        int $categoryId,
        string $stockQuantity,
        bool $isActive,
    ): Product {
        $category = $this->categories->findById($categoryId);

        if ($category === null) {
            throw new CategoryNotFoundException($categoryId);
        }

        return $this->products->create([
            'name'           => $name,
            'description'    => $description,
            'price_vnd'      => $priceVnd,
            'unit_type'      => $unitType,
            'category_id'    => $categoryId,
            'stock_quantity' => $stockQuantity,
            'is_active'      => $isActive,
        ]);
    }
}
