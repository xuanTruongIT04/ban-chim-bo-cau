<?php

declare(strict_types=1);

namespace App\Domain\Order\Enums;

enum OrderStatus: string
{
    case ChoXacNhan = 'cho_xac_nhan';
    case XacNhan    = 'xac_nhan';
    case DangGiao   = 'dang_giao';
    case HoanThanh  = 'hoan_thanh';
    case Huy        = 'huy';

    /**
     * Returns the states that this status can transition to.
     * Implements D-08 state machine, D-10 cancellation rules, D-11 back-step of 1.
     *
     * @return array<OrderStatus>
     */
    public function allowedNextStates(): array
    {
        return match ($this) {
            self::ChoXacNhan => [self::XacNhan, self::Huy],
            self::XacNhan    => [self::ChoXacNhan, self::DangGiao, self::Huy],
            self::DangGiao   => [self::XacNhan, self::HoanThanh, self::Huy],
            self::HoanThanh  => [],
            self::Huy        => [],
        };
    }

    /**
     * Checks if transitioning to $next is allowed.
     */
    public function canTransitionTo(OrderStatus $next): bool
    {
        return in_array($next, $this->allowedNextStates(), true);
    }

    /**
     * Whether this order can be cancelled.
     * Per D-10: cancellable from any state except hoan_thanh.
     */
    public function isCancellable(): bool
    {
        return $this !== self::HoanThanh;
    }

    /**
     * Vietnamese display label.
     */
    public function label(): string
    {
        return match ($this) {
            self::ChoXacNhan => 'Chờ xác nhận',
            self::XacNhan    => 'Xác nhận',
            self::DangGiao   => 'Đang giao',
            self::HoanThanh  => 'Hoàn thành',
            self::Huy        => 'Hủy',
        };
    }
}
