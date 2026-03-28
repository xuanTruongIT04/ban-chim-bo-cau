<?php

declare(strict_types=1);

namespace App\Application\Product\Actions;

use App\Domain\Product\Exceptions\ProductNotFoundException;
use App\Domain\Product\Repositories\ProductRepositoryInterface;

final class DeleteProductAction
{
    public function __construct(
        private readonly ProductRepositoryInterface $products,
    ) {}

    public function handle(int $id): void
    {
        $existing = $this->products->findById($id);

        if ($existing === null) {
            throw new ProductNotFoundException($id);
        }

        $this->products->delete($id);
    }
}
