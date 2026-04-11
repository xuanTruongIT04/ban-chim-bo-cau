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
     * Thêm header `Authorization: Bearer {token}` cho tất cả admin request.
     *
     * @unauthenticated
     *
     * @bodyParam email string required Email admin. Example: admin@example.com
     * @bodyParam password string required Mật khẩu. Example: password
     *
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "token": "1|abc123xyz...",
     *     "expires_at": "2026-04-28T00:00:00.000000Z"
     *   }
     * }
     * @response 422 {
     *   "message": "Email hoặc mật khẩu không đúng.",
     *   "errors": {
     *     "email": ["Email hoặc mật khẩu không đúng."]
     *   }
     * }
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

    /**
     * Thông tin admin hiện tại
     *
     * Trả về thông tin profile của admin đang đăng nhập dựa theo token.
     *
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "id": 1,
     *     "name": "Admin",
     *     "email": "admin@example.com",
     *     "role": "admin"
     *   }
     * }
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data'    => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'role'  => 'admin',
            ],
        ]);
    }

    /**
     * Đăng xuất admin
     *
     * Thu hồi Sanctum token hiện tại. Token sẽ không còn hợp lệ sau khi đăng xuất.
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Đã đăng xuất thành công."
     * }
     */
    public function logout(Request $request): JsonResponse
    {
        $this->logoutAction->handle($request);

        return response()->json([
            'success' => true,
            'message' => 'Đã đăng xuất thành công.',
        ]);
    }
}
