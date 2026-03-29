<?php

declare(strict_types=1);

namespace App\Presentation\Http\Middleware;

use App\Domain\Order\Repositories\CartRepositoryInterface;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ResolveCartToken
{
    public function __construct(
        private readonly CartRepositoryInterface $carts,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->header('X-Cart-Token');

        if ($token === null || $token === '') {
            return response()->json([
                'success' => false,
                'code'    => 'CART_TOKEN_REQUIRED',
                'message' => 'Thiếu X-Cart-Token header.',
                'errors'  => (object) [],
            ], 401);
        }

        $cart = $this->carts->findByToken($token);

        if ($cart === null) {
            return response()->json([
                'success' => false,
                'code'    => 'CART_NOT_FOUND',
                'message' => 'Giỏ hàng không tồn tại hoặc đã hết hạn.',
                'errors'  => (object) [],
            ], 404);
        }

        $request->attributes->set('cart', $cart);

        return $next($request);
    }
}
