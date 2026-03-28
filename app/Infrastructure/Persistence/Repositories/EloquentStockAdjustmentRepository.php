<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Product\Entities\StockAdjustment;
use App\Domain\Product\Enums\AdjustmentType;
use App\Domain\Product\Repositories\StockAdjustmentRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\Models\StockAdjustmentModel;
use App\Infrastructure\Persistence\Mappers\StockAdjustmentMapper;

final class EloquentStockAdjustmentRepository implements StockAdjustmentRepositoryInterface
{
    public function create(int $productId, int $adminUserId, string $delta, AdjustmentType $type, ?string $note, string $stockBefore, string $stockAfter): StockAdjustment
    {
        $model = StockAdjustmentModel::create([
            'product_id'      => $productId,
            'admin_user_id'   => $adminUserId,
            'delta'           => $delta,
            'adjustment_type' => $type->value,
            'note'            => $note,
            'stock_before'    => $stockBefore,
            'stock_after'     => $stockAfter,
        ]);

        return StockAdjustmentMapper::toDomain($model);
    }

    /** @return array{data: StockAdjustment[], total: int} */
    public function paginateByProduct(int $productId, int $perPage, int $page): array
    {
        $query = StockAdjustmentModel::where('product_id', $productId)
            ->orderByDesc('created_at');

        $total = $query->count();
        $models = $query->forPage($page, $perPage)->get();

        return [
            'data'  => $models->map(fn (StockAdjustmentModel $m) => StockAdjustmentMapper::toDomain($m))->all(),
            'total' => $total,
        ];
    }
}
