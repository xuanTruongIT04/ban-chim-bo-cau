<?php

declare(strict_types=1);

namespace App\Presentation\Http\Controllers\Auth;

use App\Application\Auth\Actions\LoginAdminAction;
use App\Application\Auth\Actions\LogoutAdminAction;
use App\Presentation\Http\Requests\LoginRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Auth
 *
 * Xác thực admin (Sanctum token)
 */
final class AuthController
{
    public function __construct(
        private readonly LoginAdminAction $loginAction,
        private readonly LogoutAdminAction $logoutAction,
    ) {}

    /**
     * Đăng nhập admin
     *
     * Trả về Sanctum token để sử dụng cho các admin endpoint.
     *
     * @unauthenticated
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $tokenData = $this->loginAction->handle(
            email: $request->validated('email'),
            password: $request->validated('password'),
        );

        return response()->json([
            'success' => true,
            'data'    => [
                'token'      => $tokenData['token'],
                'expires_at' => $tokenData['expires_at'],
            ],
        ], 200);
    }

    public function logout(Request $request): JsonResponse
    {
        $this->logoutAction->handle($request);

        return response()->json([
            'success' => true,
            'message' => 'Đã đăng xuất thành công.',
        ]);
    }
}
