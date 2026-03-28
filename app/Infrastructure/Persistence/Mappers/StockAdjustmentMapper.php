<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Mappers;

use App\Domain\Product\Entities\StockAdjustment;
use App\Infrastructure\Persistence\Eloquent\Models\StockAdjustmentModel;

final class StockAdjustmentMapper
{
    public static function toDomain(StockAdjustmentModel $model): StockAdjustment
    {
        return new StockAdjustment(
            id: $model->id,
            productId: (int) $model->product_id,
            adminUserId: (int) $model->admin_user_id,
            delta: (string) $model->delta,
            adjustmentType: $model->adjustment_type,
            note: $model->note,
            stockBefore: (string) $model->stock_before,
            stockAfter: (string) $model->stock_after,
            createdAt: $model->created_at->toIso8601String(),
        );
    }
}
