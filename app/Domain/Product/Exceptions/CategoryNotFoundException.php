<?php

declare(strict_types=1);

namespace App\Domain\Product\Exceptions;

final class CategoryNotFoundException extends \DomainException
{
    public function __construct(int $id)
    {
        parent::__construct("Danh mục #{$id} không tồn tại.");
    }
}
