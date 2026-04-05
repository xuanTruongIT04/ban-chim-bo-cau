<?php

declare(strict_types=1);

namespace App\Presentation\Http\Controllers\Public;

use App\Application\Order\Actions\AddToCartAction;
use App\Application\Order\Actions\CreateCartAction;
use App\Application\Order\Actions\RemoveCartItemAction;
use App\Application\Order\Actions\UpdateCartItemAction;
use App\Domain\Order\Entities\Cart;
use App\Infrastructure\Persistence\Eloquent\Models\CartModel;
use App\Presentation\Http\Requests\AddToCartRequest;
use App\Presentation\Http\Requests\UpdateCartItemRequest;
use App\Presentation\Http\Resources\CartResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Public > Giỏ hàng
 *
 * Quản lý giỏ hàng khách (không cần đăng nhập)
 *
 * @unauthenticated
 */
final class CartController
{
    public function __construct(
        private readonly CreateCartAction $createCart,
        private readonly AddToCartAction $addToCart,
        private readonly UpdateCartItemAction $updateCartItem,
        private readonly RemoveCartItemAction $removeCartItem,
    ) {}

    /**
     * Tạo giỏ hàng mới
     *
     * Trả về UUID token để sử dụng trong header X-Cart-Token cho các request tiếp theo.
     * Token có hiệu lực 7 ngày.
     *
     * @response 201 {
     *   "success": true,
     *   "data": {
     *     "token": "550e8400-e29b-41d4-a716-446655440000",
     *     "expires_at": "2026-04-05T12:00:00.000000Z"
     *   }
     * }
     */
    public function store(): JsonResponse
    {
        $cart = $this->createCart->handle();

        return response()->json([
            'success' => true,
            'data'    => [
                'token'      => $cart->token,
                'expires_at' => $cart->expiresAt,
            ],
        ], 201);
    }

    /**
     * Xem giỏ hàng
     *
     * Trả về danh sách sản phẩm trong giỏ với giá hiện tại và tổng tiền.
     *
     * @header X-Cart-Token required UUID token từ POST /cart. Example: 550e8400-e29b-41d4-a716-446655440000
     *
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "token": "550e8400-e29b-41d4-a716-446655440000",
     *     "expires_at": "2026-04-05T12:00:00.000000Z",
     *     "total_amount": 170000,
     *     "items": [
     *       {
     *         "id": 1,
     *         "product_id": 1,
     *         "product_name": "Bồ câu sống",
     *         "quantity": "2.000",
     *         "unit_price": 85000,
     *         "subtotal": 170000
     *       }
     *     ]
     *   }
     * }
     */
    public function show(Request $request): JsonResponse
    {
        /** @var Cart $domainCart */
        $domainCart = $request->attributes->get('cart');

        $eloquentCart = CartModel::query()
            ->where('id', $domainCart->id)
            ->with(['items.product.images' => fn ($q) => $q->where('is_primary', true)])
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data'    => new CartResource($eloquentCart),
        ]);
    }

    /**
     * Thêm sản phẩm vào giỏ
     *
     * Nếu sản phẩm đã có trong giỏ, số lượng sẽ được cộng thêm.
     * Trả về giỏ hàng đầy đủ sau khi thêm.
     *
     * @header X-Cart-Token required UUID token từ POST /cart. Example: 550e8400-e29b-41d4-a716-446655440000
     *
     * @bodyParam product_id integer required ID sản phẩm. Example: 1
     * @bodyParam quantity numeric required Số lượng (> 0). Example: 2
     *
     * @response 201 {
     *   "success": true,
     *   "data": {
     *     "token": "550e8400-e29b-41d4-a716-446655440000",
     *     "total_amount": 170000,
     *     "items": [
     *       {
     *         "id": 1,
     *         "product_id": 1,
     *         "product_name": "Bồ câu sống",
     *         "quantity": "2.000",
     *         "unit_price": 85000,
     *         "subtotal": 170000
     *       }
     *     ]
     *   }
     * }
     */
    public function addItem(AddToCartRequest $request): JsonResponse
    {
        /** @var Cart $domainCart */
        $domainCart = $request->attributes->get('cart');

        $this->addToCart->handle(
            cartId: $domainCart->id,
            productId: (int) $request->validated('product_id'),
            quantity: (string) $request->validated('quantity'),
        );

        $eloquentCart = CartModel::query()
            ->where('id', $domainCart->id)
            ->with(['items.product.images' => fn ($q) => $q->where('is_primary', true)])
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data'    => new CartResource($eloquentCart),
        ], 201);
    }

    /**
     * Cập nhật số lượng sản phẩm trong giỏ
     *
     * Cập nhật số lượng mục giỏ hàng theo ID mục.
     *
     * @header X-Cart-Token required UUID token từ POST /cart. Example: 550e8400-e29b-41d4-a716-446655440000
     *
     * @bodyParam quantity numeric required Số lượng mới (> 0). Example: 3
     *
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "token": "550e8400-e29b-41d4-a716-446655440000",
     *     "total_amount": 255000,
     *     "items": [
     *       {
     *         "id": 1,
     *         "product_id": 1,
     *         "quantity": "3.000",
     *         "unit_price": 85000,
     *         "subtotal": 255000
     *       }
     *     ]
     *   }
     * }
     */
    public function updateItem(UpdateCartItemRequest $request, int $item): JsonResponse
    {
        /** @var Cart $domainCart */
        $domainCart = $request->attributes->get('cart');

        $this->updateCartItem->handle(
            cartItemId: $item,
            quantity: (string) $request->validated('quantity'),
        );

        $eloquentCart = CartModel::query()
            ->where('id', $domainCart->id)
            ->with(['items.product.images' => fn ($q) => $q->where('is_primary', true)])
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data'    => new CartResource($eloquentCart),
        ]);
    }

    /**
     * Xóa sản phẩm khỏi giỏ hàng
     *
     * Xóa một mục khỏi giỏ hàng theo ID mục.
     *
     * @header X-Cart-Token required UUID token từ POST /cart. Example: 550e8400-e29b-41d4-a716-446655440000
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Đã xóa sản phẩm khỏi giỏ hàng."
     * }
     */
    public function removeItem(Request $request, int $item): JsonResponse
    {
        $this->removeCartItem->handle($item);

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa sản phẩm khỏi giỏ hàng.',
        ]);
    }
}
