<?php

declare(strict_types=1);

namespace App\Presentation\Http\Controllers\Admin;

use App\Application\Admin\Actions\GetDashboardStatsAction;
use Illuminate\Http\JsonResponse;

/**
 * @group Admin > Dashboard
 *
 * Tong quan don hang cho admin
 */
final class DashboardController
{
    public function __construct(
        private readonly GetDashboardStatsAction $action,
    ) {}

    /**
     * Dashboard tong quan don hang
     *
     * Tra ve so don hang theo tung trang thai.
     *
     * @response 200 {"success": true, "data": {"orders_by_status": {"cho_xac_nhan": 5, "xac_nhan": 3, "dang_giao": 2, "hoan_thanh": 10, "huy": 1}}}
     */
    public function index(): JsonResponse
    {
        $stats = $this->action->handle();

        return response()->json([
            'success' => true,
            'data'    => ['orders_by_status' => $stats],
        ]);
    }
}
