<?php

declare(strict_types=1);

namespace App\Domain\Order\Exceptions;

final class OrderNotFoundException extends \DomainException
{
    public function __construct(?int $id = null)
    {
        $message = $id !== null
            ? "Không tìm thấy đơn hàng #{$id}."
            : 'Không tìm thấy đơn hàng.';

        parent::__construct($message);
    }
}
