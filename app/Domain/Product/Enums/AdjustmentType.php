<?php

declare(strict_types=1);

namespace App\Domain\Product\Enums;

enum AdjustmentType: string
{
    case NhapHang = 'nhap_hang';
    case KiemKe = 'kiem_ke';
    case HuHong = 'hu_hong';
    case Khac = 'khac';
}
