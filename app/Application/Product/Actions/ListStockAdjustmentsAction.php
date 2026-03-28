<?php

declare(strict_types=1);

namespace App\Application\Product\Actions;

use App\Domain\Product\Exceptions\ProductNotFoundException;
use App\Domain\Product\Repositories\ProductRepositoryInterface;
use App\Domain\Product\Repositories\StockAdjustmentRepositoryInterface;

final class ListStockAdjustmentsAction
{
    public function __construct(
        private readonly StockAdjustmentRepositoryInterface $adjustments,
        private readonly ProductRepositoryInterface $products,
    ) {}

    /**
     * @return array{data: \App\Domain\Product\Entities\StockAdjustment[], total: int}
     */
    public function handle(int $productId, int $perPage, int $page): array
    {
        $product = $this->products->findById($productId);

        if ($product === null) {
            throw new ProductNotFoundException($productId);
        }

        return $this->adjustments->paginateByProduct($productId, $perPage, $page);
    }
}
