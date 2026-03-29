<?php

declare(strict_types=1);

namespace App\Domain\Order\Enums;

enum PaymentMethod: string
{
    case Cod          = 'cod';
    case ChuyenKhoan  = 'chuyen_khoan';

    /**
     * Vietnamese display label.
     */
    public function label(): string
    {
        return match ($this) {
            self::Cod         => 'Thanh toán khi nhận hàng (COD)',
            self::ChuyenKhoan => 'Chuyển khoản ngân hàng',
        };
    }
}
