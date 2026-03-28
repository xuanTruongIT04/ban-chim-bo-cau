<?php

use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {
    // Public routes (no auth required)
    // Route::post('/admin/login', ...) — Plan 02

    // Admin-only routes (Sanctum protected)
    Route::middleware('auth:sanctum')->prefix('admin')->name('admin.')->group(function () {
        // Route::post('/logout', ...) — Plan 02
    });

    // Public customer routes (future phases)
    Route::prefix('products')->name('products.')->group(function () {
        // Phase 2+
    });
});
