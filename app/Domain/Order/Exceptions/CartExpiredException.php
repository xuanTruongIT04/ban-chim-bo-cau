<?php

declare(strict_types=1);

namespace App\Domain\Order\Exceptions;

final class CartExpiredException extends \DomainException
{
    public function __construct()
    {
        parent::__construct('Giỏ hàng đã hết hạn. Vui lòng tạo giỏ hàng mới.');
    }
}
