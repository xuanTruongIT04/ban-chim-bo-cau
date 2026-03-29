<?php

declare(strict_types=1);

namespace App\Presentation\Http\Controllers\Public;

use App\Application\Order\Actions\PlaceOrderAction;
use App\Domain\Order\Enums\PaymentMethod;
use App\Domain\Order\Exceptions\CartNotFoundException;
use App\Domain\Order\Repositories\CartRepositoryInterface;
use App\Presentation\Http\Requests\CheckoutRequest;
use App\Presentation\Http\Resources\OrderResource;
use Illuminate\Http\JsonResponse;

/**
 * @group Checkout
 *
 * Đặt hàng từ giỏ hàng (không cần đăng nhập)
 *
 * @unauthenticated
 */
final class CheckoutController
{
    public function __construct(
        private readonly CartRepositoryInterface $carts,
        private readonly PlaceOrderAction $placeOrderAction,
    ) {}

    /**
     * Place an order from cart
     *
     * Đặt hàng từ giỏ hàng. Yêu cầu header `X-Cart-Token` và `Idempotency-Key` (UUID).
     * Tồn kho được trừ atomic trong cùng một transaction.
     *
     * @bodyParam customer_name string required Họ tên khách hàng. Example: Nguyễn Văn A
     * @bodyParam customer_phone string required Số điện thoại 10 chữ số bắt đầu 0. Example: 0901234567
     * @bodyParam delivery_address string required Địa chỉ giao hàng. Example: 123 Đường ABC, TP.HCM
     * @bodyParam payment_method string required Phương thức thanh toán: cod hoặc chuyen_khoan. Example: cod
     *
     * @response 201 {
     *   "success": true,
     *   "data": {
     *     "id": 1,
     *     "customer_name": "Nguyễn Văn A",
     *     "customer_phone": "0901234567",
     *     "delivery_address": "123 Đường ABC, TP.HCM",
     *     "order_status": "cho_xac_nhan",
     *     "order_status_label": "Chờ xác nhận",
     *     "payment_method": "cod",
     *     "payment_method_label": "Thanh toán khi nhận hàng (COD)",
     *     "payment_status": "chua_thanh_toan",
     *     "payment_status_label": "Chưa thanh toán",
     *     "delivery_method": null,
     *     "delivery_method_label": null,
     *     "total_amount": "150000",
     *     "created_by": null,
     *     "items": []
     *   }
     * }
     */
    public function store(CheckoutRequest $request): JsonResponse
    {
        $cartToken = $request->header('X-Cart-Token');

        if ($cartToken === null || $cartToken === '') {
            throw new CartNotFoundException();
        }

        $cart = $this->carts->findByToken($cartToken);

        if ($cart === null) {
            throw new CartNotFoundException($cartToken);
        }

        $validated = $request->validated();

        $order = $this->placeOrderAction->handle(
            cart: $cart,
            customerName: $validated['customer_name'],
            customerPhone: $validated['customer_phone'],
            deliveryAddress: $validated['delivery_address'],
            paymentMethod: PaymentMethod::from($validated['payment_method']),
        );

        $responseData = [
            'success' => true,
            'data'    => new OrderResource($order),
        ];

        // Per D-19: include bank account info for chuyen_khoan
        if ($validated['payment_method'] === 'chuyen_khoan') {
            $responseData['bank_info'] = config('bank');
        }

        return response()->json($responseData, 201);
    }
}
