<?php

declare(strict_types=1);

namespace App\Domain\Order\Exceptions;

final class InactiveProductInCartException extends \DomainException
{
    public function __construct(string $productName)
    {
        parent::__construct("Sản phẩm '{$productName}' hiện không còn bán.");
    }
}
