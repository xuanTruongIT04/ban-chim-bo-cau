<?php

declare(strict_types=1);

namespace App\Domain\Order\Exceptions;

use App\Domain\Order\Enums\OrderStatus;

final class InvalidOrderTransitionException extends \DomainException
{
    public function __construct(OrderStatus $from, OrderStatus $to)
    {
        parent::__construct(
            "Không thể chuyển trạng thái đơn hàng từ '{$from->label()}' sang '{$to->label()}'."
        );
    }
}
