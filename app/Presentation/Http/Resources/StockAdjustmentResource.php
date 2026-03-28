<?php

declare(strict_types=1);

namespace App\Presentation\Http\Resources;

use App\Domain\Product\Entities\StockAdjustment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin StockAdjustment
 */
final class StockAdjustmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var StockAdjustment $adjustment */
        $adjustment = $this->resource;

        return [
            'id'              => $adjustment->id,
            'product_id'      => $adjustment->productId,
            'admin_user_id'   => $adjustment->adminUserId,
            'delta'           => $adjustment->delta,
            'adjustment_type' => $adjustment->adjustmentType->value,
            'note'            => $adjustment->note,
            'stock_before'    => $adjustment->stockBefore,
            'stock_after'     => $adjustment->stockAfter,
            'created_at'      => $adjustment->createdAt,
        ];
    }
}
