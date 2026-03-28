<?php

declare(strict_types=1);

namespace App\Presentation\Http\Controllers\Auth;

use App\Application\Auth\Actions\LoginAdminAction;
use App\Application\Auth\Actions\LogoutAdminAction;
use App\Presentation\Http\Requests\LoginRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AuthController
{
    public function __construct(
        private readonly LoginAdminAction $loginAction,
        private readonly LogoutAdminAction $logoutAction,
    ) {}

    public function login(LoginRequest $request): JsonResponse
    {
        $token = $this->loginAction->handle(
            email: $request->validated('email'),
            password: $request->validated('password'),
        );

        return response()->json([
            'success' => true,
            'token'   => $token,
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
