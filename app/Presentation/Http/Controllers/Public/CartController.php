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
     * @header X-Cart-Token required
     */
    public function show(Request $request): JsonResponse
    {
        /** @var Cart $domainCart */
        $domainCart = $request->attributes->get('cart');

        $eloquentCart = CartModel::query()
            ->where('id', $domainCart->id)
            ->with('items.product')
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
     *
     * @header X-Cart-Token required
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
            ->with('items.product')
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data'    => new CartResource($eloquentCart),
        ], 201);
    }

    /**
     * Cập nhật số lượng sản phẩm trong giỏ
     *
     * @header X-Cart-Token required
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
            ->with('items.product')
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data'    => new CartResource($eloquentCart),
        ]);
    }

    /**
     * Xóa sản phẩm khỏi giỏ hàng
     *
     * @header X-Cart-Token required
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
