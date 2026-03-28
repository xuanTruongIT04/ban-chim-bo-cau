<?php

declare(strict_types=1);

namespace App\Domain\Product\Exceptions;

final class InsufficientStockException extends \DomainException
{
    public function __construct(string $stockBefore, string $delta)
    {
        parent::__construct("Tồn kho không đủ. Hiện có: {$stockBefore}, yêu cầu thay đổi: {$delta}");
    }
}
