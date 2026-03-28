<?php

declare(strict_types=1);

namespace App\Domain\Product\Entities;

final class ProductImage
{
    public function __construct(
        public readonly int $id,
        public readonly int $productId,
        public readonly string $path,
        public readonly string $thumbnailPath,
        public readonly bool $isPrimary,
        public readonly int $sortOrder,
    ) {}
}
