<?php

declare(strict_types=1);

namespace App\Presentation\Http\Controllers\Admin;

use App\Application\Order\Actions\AdminPlaceOrderAction;
use App\Domain\Order\Enums\PaymentMethod;
use App\Domain\Order\Exceptions\OrderNotFoundException;
use App\Domain\Order\Repositories\OrderRepositoryInterface;
use App\Presentation\Http\Requests\AdminPlaceOrderRequest;
use App\Presentation\Http\Resources\OrderResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Admin - Orders
 *
 * Quản lý đơn hàng (yêu cầu đăng nhập admin)
 */
final class OrderController
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
        private readonly AdminPlaceOrderAction $adminPlaceOrderAction,
    ) {}

    /**
     * Create a manual order (admin)
     *
     * Admin nhập đơn hàng thủ công cho khách Zalo/điện thoại.
     * Dùng cùng cơ chế lock atomic như checkout khách hàng.
     *
     * @bodyParam customer_name string required Họ tên khách hàng. Example: Trần Thị B
     * @bodyParam customer_phone string required Số điện thoại 10 chữ số bắt đầu 0. Example: 0909876543
     * @bodyParam delivery_address string required Địa chỉ giao hàng. Example: 456 Đường XYZ, Hà Nội
     * @bodyParam payment_method string required Phương thức thanh toán: cod hoặc chuyen_khoan. Example: cod
     * @bodyParam items array required Danh sách sản phẩm.
     * @bodyParam items[].product_id integer required Mã sản phẩm. Example: 1
     * @bodyParam items[].quantity numeric required Số lượng. Example: 2
     *
     * @response 201 {
     *   "success": true,
     *   "data": {
     *     "id": 2,
     *     "customer_name": "Trần Thị B",
     *     "order_status": "cho_xac_nhan",
     *     "payment_status": "chua_thanh_toan",
     *     "created_by": 1
     *   }
     * }
     */
    public function store(AdminPlaceOrderRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $order = $this->adminPlaceOrderAction->handle(
            customerName: $validated['customer_name'],
            customerPhone: $validated['customer_phone'],
            deliveryAddress: $validated['delivery_address'],
            paymentMethod: PaymentMethod::from($validated['payment_method']),
            items: $validated['items'],
            adminUserId: (int) $request->user()->id,
        );

        return response()->json([
            'success' => true,
            'data'    => new OrderResource($order),
        ], 201);
    }

    /**
     * Get order detail (admin)
     *
     * Xem chi tiết đơn hàng theo ID.
     *
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "id": 1,
     *     "customer_name": "Nguyễn Văn A",
     *     "order_status": "cho_xac_nhan",
     *     "items": []
     *   }
     * }
     */
    public function show(Request $request, int $order): JsonResponse
    {
        $orderEntity = $this->orders->findById($order);

        if ($orderEntity === null) {
            throw new OrderNotFoundException($order);
        }

        return response()->json([
            'success' => true,
            'data'    => new OrderResource($orderEntity),
        ]);
    }
}
