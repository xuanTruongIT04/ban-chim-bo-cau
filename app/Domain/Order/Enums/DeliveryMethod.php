<?php

declare(strict_types=1);

namespace App\Domain\Order\Enums;

enum DeliveryMethod: string
{
    case NoiTinh   = 'noi_tinh';
    case NgoaiTinh = 'ngoai_tinh';

    /**
     * Vietnamese display label.
     */
    public function label(): string
    {
        return match ($this) {
            self::NoiTinh   => 'Nội tỉnh (tự giao)',
            self::NgoaiTinh => 'Ngoại tỉnh (xe khách)',
        };
    }
}
