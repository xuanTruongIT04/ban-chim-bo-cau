<?php

declare(strict_types=1);

namespace App\Application\Admin\Actions;

use App\Domain\Order\Repositories\OrderRepositoryInterface;

final class GetDashboardStatsAction
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
    ) {}

    /** @return array<string, int> */
    public function handle(): array
    {
        return $this->orders->countByStatus();
    }
}
