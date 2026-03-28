<?php

declare(strict_types=1);

namespace App\Domain\Product\Entities;

use App\Domain\Product\Enums\UnitType;

final class Product
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly ?string $description,
        public readonly int $priceVnd,
        public readonly UnitType $unitType,
        public readonly int $categoryId,
        public readonly string $stockQuantity, // string for DECIMAL precision
        public readonly bool $isActive,
        public readonly ?string $createdAt,
        public readonly ?string $updatedAt,
    ) {}
}
