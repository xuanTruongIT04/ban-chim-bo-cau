<?php

declare(strict_types=1);

namespace App\Domain\Order\Enums;

enum PaymentStatus: string
{
    case ChuaThanhToan = 'chua_thanh_toan';
    case DaThanhToan   = 'da_thanh_toan';

    /**
     * Vietnamese display label.
     */
    public function label(): string
    {
        return match ($this) {
            self::ChuaThanhToan => 'Chưa thanh toán',
            self::DaThanhToan   => 'Đã thanh toán',
        };
    }
}
