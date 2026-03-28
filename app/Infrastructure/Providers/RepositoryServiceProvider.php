<?php

declare(strict_types=1);

namespace App\Infrastructure\Providers;

use App\Domain\Auth\Repositories\AdminUserRepositoryInterface;
use App\Domain\Product\Repositories\CategoryRepositoryInterface;
use App\Domain\Product\Repositories\ProductRepositoryInterface;
use App\Domain\Product\Repositories\StockAdjustmentRepositoryInterface;
use App\Infrastructure\Persistence\Repositories\EloquentAdminUserRepository;
use App\Infrastructure\Persistence\Repositories\EloquentCategoryRepository;
use App\Infrastructure\Persistence\Repositories\EloquentProductRepository;
use App\Infrastructure\Persistence\Repositories\EloquentStockAdjustmentRepository;
use Illuminate\Support\ServiceProvider;

final class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            AdminUserRepositoryInterface::class,
            EloquentAdminUserRepository::class,
        );

        $this->app->bind(
            CategoryRepositoryInterface::class,
            EloquentCategoryRepository::class,
        );

        $this->app->bind(
            ProductRepositoryInterface::class,
            EloquentProductRepository::class,
        );

        $this->app->bind(
            StockAdjustmentRepositoryInterface::class,
            EloquentStockAdjustmentRepository::class,
        );
    }
}
