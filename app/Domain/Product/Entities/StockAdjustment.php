<?php

declare(strict_types=1);

namespace App\Domain\Product\Entities;

use App\Domain\Product\Enums\AdjustmentType;

final class StockAdjustment
{
    public function __construct(
        public readonly int $id,
        public readonly int $productId,
        public readonly int $adminUserId,
        public readonly string $delta, // string for DECIMAL
        public readonly AdjustmentType $adjustmentType,
        public readonly ?string $note,
        public readonly string $stockBefore, // string for DECIMAL
        public readonly string $stockAfter, // string for DECIMAL
        public readonly string $createdAt,
    ) {}
}
