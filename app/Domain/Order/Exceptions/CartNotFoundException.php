<?php

declare(strict_types=1);

namespace App\Domain\Order\Exceptions;

final class CartNotFoundException extends \DomainException
{
    public function __construct(?string $token = null)
    {
        $message = $token !== null
            ? "Giỏ hàng '{$token}' không tồn tại hoặc đã hết hạn."
            : 'Giỏ hàng không tồn tại hoặc đã hết hạn.';

        parent::__construct($message);
    }
}
