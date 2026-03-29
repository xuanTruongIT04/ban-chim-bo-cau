<?php

declare(strict_types=1);

namespace App\Presentation\Http\Controllers\Admin;

use App\Application\Order\Actions\AdminPlaceOrderAction;
use App\Application\Order\Actions\CancelOrderAction;
use App\Application\Order\Actions\ConfirmPaymentAction;
use App\Application\Order\Actions\UpdateOrderStatusAction;
use App\Domain\Order\Enums\DeliveryMethod;
use App\Domain\Order\Enums\OrderStatus;
use App\Domain\Order\Enums\PaymentMethod;
use App\Domain\Order\Exceptions\OrderNotFoundException;
use App\Domain\Order\Repositories\OrderRepositoryInterface;
use App\Presentation\Http\Requests\AdminPlaceOrderRequest;
use App\Presentation\Http\Requests\UpdateDeliveryMethodRequest;
use App\Presentation\Http\Requests\UpdateOrderStatusRequest;
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
        private readonly UpdateOrderStatusAction $updateOrderStatusAction,
        private readonly CancelOrderAction $cancelOrderAction,
        private readonly ConfirmPaymentAction $confirmPaymentAction,
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

    /**
     * Update order status (admin)
     *
     * Chuyển trạng thái đơn hàng theo state machine hợp lệ.
     * Không dùng endpoint này để hủy đơn — dùng POST /orders/{order}/cancel.
     *
     * @bodyParam status string required Trạng thái mới: cho_xac_nhan, xac_nhan, dang_giao, hoặc hoan_thanh. Example: xac_nhan
     *
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "id": 1,
     *     "order_status": "xac_nhan"
     *   }
     * }
     */
    public function updateStatus(UpdateOrderStatusRequest $request, int $order): JsonResponse
    {
        $newStatus   = OrderStatus::from($request->validated('status'));
        $orderEntity = $this->updateOrderStatusAction->handle($order, $newStatus);

        return response()->json([
            'success' => true,
            'data'    => new OrderResource($orderEntity),
        ]);
    }

    /**
     * Cancel order (admin)
     *
     * Hủy đơn hàng và hoàn lại tồn kho trong cùng một transaction.
     * Không thể hủy đơn đã hoàn thành (hoan_thanh).
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Don hang da duoc huy.",
     *   "data": {
     *     "id": 1,
     *     "order_status": "huy"
     *   }
     * }
     */
    public function cancel(int $order): JsonResponse
    {
        $orderEntity = $this->cancelOrderAction->handle($order);

        return response()->json([
            'success' => true,
            'message' => 'Don hang da duoc huy.',
            'data'    => new OrderResource($orderEntity),
        ]);
    }

    /**
     * Confirm payment (admin)
     *
     * Xác nhận thanh toán đơn hàng (payment_status -> da_thanh_toan).
     * Idempotent: gọi nhiều lần vẫn trả về 200 với da_thanh_toan.
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Da xac nhan thanh toan.",
     *   "data": {
     *     "id": 1,
     *     "payment_status": "da_thanh_toan"
     *   }
     * }
     */
    public function confirmPayment(int $order): JsonResponse
    {
        $orderEntity = $this->confirmPaymentAction->handle($order);

        return response()->json([
            'success' => true,
            'message' => 'Da xac nhan thanh toan.',
            'data'    => new OrderResource($orderEntity),
        ]);
    }

    /**
     * Set delivery method (admin)
     *
     * Gán hình thức giao hàng cho đơn: noi_tinh (tự giao) hoặc ngoai_tinh (xe khách).
     *
     * @bodyParam delivery_method string required Hình thức giao hàng: noi_tinh hoặc ngoai_tinh. Example: noi_tinh
     *
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "id": 1,
     *     "delivery_method": "noi_tinh"
     *   }
     * }
     */
    public function updateDeliveryMethod(UpdateDeliveryMethodRequest $request, int $order): JsonResponse
    {
        $orderEntity = $this->orders->findById($order);

        if ($orderEntity === null) {
            throw new OrderNotFoundException($order);
        }

        $method      = DeliveryMethod::from($request->validated('delivery_method'));
        $orderEntity = $this->orders->updateDeliveryMethod($order, $method);

        return response()->json([
            'success' => true,
            'data'    => new OrderResource($orderEntity),
        ]);
    }
}
