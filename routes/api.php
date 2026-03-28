<?php

use App\Presentation\Http\Controllers\Admin\CategoryController;
use App\Presentation\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Presentation\Http\Controllers\Auth\AuthController;
use App\Presentation\Http\Controllers\Public\ProductController as PublicProductController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {
    // Public routes (no auth required) — AUTH-03: guests don't need authentication
    Route::post('/admin/login', [AuthController::class, 'login'])
        ->name('auth.login');

    // Admin-only routes (Sanctum protected) — AUTH-01, AUTH-04
    Route::middleware('auth:sanctum')->prefix('admin')->name('admin.')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])
            ->name('auth.logout');

        Route::apiResource('categories', CategoryController::class);

        Route::apiResource('products', AdminProductController::class);
        Route::patch('products/{product}/toggle-active', [AdminProductController::class, 'toggleActive'])
            ->name('products.toggle-active');
    });

    // Public customer routes — AUTH-03, PROD-01, PROD-05
    Route::prefix('products')->name('products.')->group(function () {
        Route::get('/', [PublicProductController::class, 'index'])->name('index');
        Route::get('/{product}', [PublicProductController::class, 'show'])->name('show');
    });
});
