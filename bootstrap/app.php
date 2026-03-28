<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Pure API app — never redirect unauthenticated requests; always return null
        // so AuthenticationException is thrown and our JSON handler takes over
        $middleware->redirectGuestsTo(fn (Request $request) => null);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Force JSON for all api/* routes (never return HTML) — TECH-03
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request, \Throwable $e) => $request->is('api/*')
        );

        // Normalize all exceptions to { success, code, message, errors } envelope
        $exceptions->render(function (\Throwable $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            [$status, $code, $message, $errors] = match (true) {
                $e instanceof \Illuminate\Validation\ValidationException => [
                    422,
                    'VALIDATION_ERROR',
                    'Dữ liệu không hợp lệ.',
                    $e->errors(),
                ],
                $e instanceof \Illuminate\Auth\AuthenticationException => [
                    401,
                    'UNAUTHENTICATED',
                    'Bạn chưa đăng nhập.',
                    (object) [],
                ],
                $e instanceof \App\Exceptions\Auth\InvalidCredentialsException => [
                    401,
                    'INVALID_CREDENTIALS',
                    $e->getMessage(),
                    (object) [],
                ],
                $e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException => [
                    404,
                    'NOT_FOUND',
                    'Không tìm thấy tài nguyên.',
                    (object) [],
                ],
                $e instanceof \Symfony\Component\HttpKernel\Exception\HttpException => [
                    $e->getStatusCode(),
                    'HTTP_ERROR',
                    $e->getMessage() ?: 'Lỗi yêu cầu.',
                    (object) [],
                ],
                default => [
                    500,
                    'SERVER_ERROR',
                    app()->isProduction() ? 'Đã xảy ra lỗi. Vui lòng thử lại.' : $e->getMessage(),
                    (object) [],
                ],
            };

            return response()->json([
                'success' => false,
                'code'    => $code,
                'message' => $message,
                'errors'  => $errors,
            ], $status);
        });
    })->create();
