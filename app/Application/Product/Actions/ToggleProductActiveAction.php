<?php

declare(strict_types=1);

namespace App\Application\Product\Actions;

use App\Domain\Product\Entities\Product;
use App\Domain\Product\Exceptions\ProductNotFoundException;
use App\Domain\Product\Repositories\ProductRepositoryInterface;

final class ToggleProductActiveAction
{
    public function __construct(
        private readonly ProductRepositoryInterface $products,
    ) {}

    public function handle(int $id): Product
    {
        $product = $this->products->findById($id);

        if ($product === null) {
            throw new ProductNotFoundException($id);
        }

        return $this->products->update($id, [
            'is_active' => ! $product->isActive,
        ]);
    }
}
