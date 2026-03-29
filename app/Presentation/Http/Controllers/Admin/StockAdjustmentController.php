<?php

declare(strict_types=1);

namespace App\Presentation\Http\Controllers\Admin;

use App\Application\Product\Actions\AdjustStockAction;
use App\Application\Product\Actions\ListStockAdjustmentsAction;
use App\Domain\Product\Enums\AdjustmentType;
use App\Presentation\Http\Requests\AdjustStockRequest;
use App\Presentation\Http\Resources\StockAdjustmentResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Admin > Tồn kho
 *
 * Điều chỉnh tồn kho và xem lịch sử thay đổi
 */
final class StockAdjustmentController
{
    public function __construct(
        private readonly AdjustStockAction $adjustStockAction,
        private readonly ListStockAdjustmentsAction $listAction,
    ) {}

    /**
     * Lịch sử điều chỉnh tồn kho
     *
     * Trả về lịch sử tất cả điều chỉnh tồn kho của một sản phẩm, mới nhất trước.
     *
     * @queryParam per_page integer Số bản ghi mỗi trang (mặc định 15). Example: 15
     * @queryParam page integer Số trang. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "data": [
     *     {
     *       "id": 1,
     *       "product_id": 1,
     *       "delta": "10.000",
     *       "adjustment_type": "nhap_hang",
     *       "note": "Nhập hàng từ trại",
     *       "created_at": "2026-03-01T08:00:00.000000Z"
     *     }
     *   ],
     *   "meta": {"total": 1, "per_page": 15, "current_page": 1}
     * }
     */
    public function index(Request $request, int $product): JsonResponse
    {
        $perPage = $request->integer('per_page', 15);
        $page = $request->integer('page', 1);

        $result = $this->listAction->handle($product, $perPage, $page);

        return response()->json([
            'success' => true,
            'data'    => StockAdjustmentResource::collection($result['data']),
            'meta'    => [
                'total'        => $result['total'],
                'per_page'     => $perPage,
                'current_page' => $page,
            ],
        ]);
    }

    /**
     * Điều chỉnh tồn kho
     *
     * Cộng hoặc trừ tồn kho thủ công. Loại nhap_hang/kiem_ke cộng delta;
     * loại xuat_hang/huy_hang trừ delta. Ghi lịch sử đầy đủ với admin ID.
     *
     * @bodyParam delta numeric required Số lượng điều chỉnh (luôn dương). Example: 10
     * @bodyParam adjustment_type string required Loại điều chỉnh: nhap_hang, xuat_hang, kiem_ke, huy_hang. Example: nhap_hang
     * @bodyParam note string optional Ghi chú lý do điều chỉnh. Example: Nhập hàng từ trại
     *
     * @response 201 {
     *   "success": true,
     *   "data": {
     *     "id": 5,
     *     "product_id": 1,
     *     "delta": "10.000",
     *     "adjustment_type": "nhap_hang",
     *     "note": "Nhập hàng từ trại",
     *     "admin_user_id": 1,
     *     "created_at": "2026-03-29T08:00:00.000000Z"
     *   }
     * }
     * @response 422 {"message": "Tồn kho không đủ để trừ."}
     */
    public function store(AdjustStockRequest $request, int $product): JsonResponse
    {
        $validated = $request->validated();
        $adminUserId = (int) $request->user()->id;
        $type = AdjustmentType::from($validated['adjustment_type']);

        $adjustment = $this->adjustStockAction->handle(
            productId: $product,
            delta: (string) $validated['delta'],
            type: $type,
            note: $validated['note'] ?? null,
            adminUserId: $adminUserId,
        );

        return response()->json([
            'success' => true,
            'data'    => new StockAdjustmentResource($adjustment),
        ], 201);
    }
}
