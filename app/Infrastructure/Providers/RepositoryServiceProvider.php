<?php

declare(strict_types=1);

namespace App\Infrastructure\Providers;

use App\Domain\Auth\Repositories\AdminUserRepositoryInterface;
use App\Infrastructure\Persistence\Repositories\EloquentAdminUserRepository;
use Illuminate\Support\ServiceProvider;

final class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            AdminUserRepositoryInterface::class,
            EloquentAdminUserRepository::class,
        );
    }
}
