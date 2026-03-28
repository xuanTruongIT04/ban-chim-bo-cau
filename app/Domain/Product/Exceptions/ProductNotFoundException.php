<?php

declare(strict_types=1);

namespace App\Domain\Product\Exceptions;

final class ProductNotFoundException extends \DomainException
{
    public function __construct(int $id)
    {
        parent::__construct("Sản phẩm #{$id} không tồn tại.");
    }
}
