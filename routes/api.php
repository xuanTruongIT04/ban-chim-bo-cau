<?php

use App\Presentation\Http\Controllers\Admin\CategoryController;
use App\Presentation\Http\Controllers\Admin\OrderController;
use App\Presentation\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Presentation\Http\Controllers\Admin\ProductImageController;
use App\Presentation\Http\Controllers\Admin\StockAdjustmentController;
use App\Presentation\Http\Controllers\Auth\AuthController;
use App\Presentation\Http\Controllers\Public\CartController;
use App\Presentation\Http\Controllers\Public\CheckoutController;
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

        Route::get('products/{product}/stock-adjustments', [StockAdjustmentController::class, 'index'])
            ->name('products.stock-adjustments.index');
        Route::post('products/{product}/stock-adjustments', [StockAdjustmentController::class, 'store'])
            ->name('products.stock-adjustments.store');

        Route::post('products/{product}/images', [ProductImageController::class, 'store'])
            ->name('products.images.store');
        Route::patch('products/{product}/images/{image}/primary', [ProductImageController::class, 'setPrimary'])
            ->name('products.images.set-primary');
        Route::delete('products/{product}/images/{image}', [ProductImageController::class, 'destroy'])
            ->name('products.images.destroy');

        // Admin order routes — ORDR-03, ORDR-04, ORDR-05, ORDR-06, ORDR-07, PAYM-04, DELV-02
        Route::post('/orders', [OrderController::class, 'store'])
            ->middleware(\Infinitypaul\Idempotency\Middleware\EnsureIdempotency::class)
            ->name('orders.store');
        Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
        Route::patch('/orders/{order}/status', [OrderController::class, 'updateStatus'])
            ->name('orders.update-status');
        Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel'])
            ->name('orders.cancel');
        Route::patch('/orders/{order}/payment-status', [OrderController::class, 'confirmPayment'])
            ->name('orders.confirm-payment');
        Route::patch('/orders/{order}/delivery-method', [OrderController::class, 'updateDeliveryMethod'])
            ->name('orders.update-delivery-method');
    });

    // Public customer routes — AUTH-03, PROD-01, PROD-05
    Route::prefix('products')->name('products.')->group(function () {
        Route::get('/', [PublicProductController::class, 'index'])->name('index');
        Route::get('/{product}', [PublicProductController::class, 'show'])->name('show');
    });

    // Checkout route (public, no auth) — ORDR-01, ORDR-02
    Route::post('/checkout', [CheckoutController::class, 'store'])
        ->middleware(\Infinitypaul\Idempotency\Middleware\EnsureIdempotency::class)
        ->name('checkout');

    // Cart routes (public, no auth) — CART-01..04
    Route::post('/cart', [CartController::class, 'store'])->name('cart.store');
    Route::middleware(\App\Presentation\Http\Middleware\ResolveCartToken::class)
        ->prefix('cart')->name('cart.')->group(function () {
            Route::get('/', [CartController::class, 'show'])->name('show');
            Route::post('/items', [CartController::class, 'addItem'])->name('items.store');
            Route::patch('/items/{item}', [CartController::class, 'updateItem'])->name('items.update');
            Route::delete('/items/{item}', [CartController::class, 'removeItem'])->name('items.destroy');
        });
});
