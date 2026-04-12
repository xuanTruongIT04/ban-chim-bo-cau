<?php

declare(strict_types=1);

namespace App\Application\Product\Actions;

use App\Domain\Product\Entities\StockAdjustment;
use App\Domain\Product\Enums\AdjustmentType;
use App\Domain\Product\Exceptions\InsufficientStockException;
use App\Domain\Product\Exceptions\ProductNotFoundException;
use App\Domain\Product\Repositories\ProductRepositoryInterface;
use App\Domain\Product\Repositories\StockAdjustmentRepositoryInterface;
use Illuminate\Support\Facades\DB;

final class AdjustStockAction
{
    public function __construct(
        private readonly ProductRepositoryInterface $products,
        private readonly StockAdjustmentRepositoryInterface $adjustments,
    ) {}

    public function handle(int $productId, string $delta, AdjustmentType $type, ?string $note, int $adminUserId): StockAdjustment
    {
        return DB::transaction(function () use ($productId, $delta, $type, $note, $adminUserId): StockAdjustment {
            $product = $this->products->findByIdForUpdate($productId);

            if ($product === null) {
                throw new ProductNotFoundException($productId);
            }

            $stockBefore = $product->stockQuantity;
            $stockAfter = \bcadd($stockBefore, $delta, 3);

            if (\bccomp($stockAfter, '0', 3) < 0) {
                throw new InsufficientStockException($stockBefore, $delta);
            }

            $this->products->updateStock($productId, $stockAfter);

            return $this->adjustments->create(
                $productId,
                $adminUserId,
                $delta,
                $type,
                $note,
                $stockBefore,
                $stockAfter,
            );
        });
    }
}
