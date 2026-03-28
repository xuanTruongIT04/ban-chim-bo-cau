<?php

declare(strict_types=1);

namespace App\Domain\Product\Repositories;

use App\Domain\Product\Entities\StockAdjustment;
use App\Domain\Product\Enums\AdjustmentType;

interface StockAdjustmentRepositoryInterface
{
    public function create(int $productId, int $adminUserId, string $delta, AdjustmentType $type, ?string $note, string $stockBefore, string $stockAfter): StockAdjustment;

    /** @return array{data: StockAdjustment[], total: int} */
    public function paginateByProduct(int $productId, int $perPage, int $page): array;
}
