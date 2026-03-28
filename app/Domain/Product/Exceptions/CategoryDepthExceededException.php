<?php

declare(strict_types=1);

namespace App\Domain\Product\Exceptions;

final class CategoryDepthExceededException extends \DomainException
{
    public function __construct()
    {
        parent::__construct('Danh mục chỉ hỗ trợ tối đa 2 cấp (cha và con).');
    }
}
