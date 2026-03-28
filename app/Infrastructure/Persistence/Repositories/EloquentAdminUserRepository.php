<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Auth\Entities\AdminUser;
use App\Domain\Auth\Repositories\AdminUserRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\Models\UserModel;
use App\Infrastructure\Persistence\Mappers\UserMapper;

final class EloquentAdminUserRepository implements AdminUserRepositoryInterface
{
    public function findByEmail(string $email): ?AdminUser
    {
        $model = UserModel::where('email', $email)->first();

        if ($model === null) {
            return null;
        }

        return UserMapper::toDomain($model);
    }

    public function createToken(AdminUser $user): string
    {
        $model = UserModel::findOrFail($user->id);

        return $model->createToken(
            name: 'admin-session',
            abilities: ['*'],
            expiresAt: now()->addMinutes((int) config('sanctum.expiration', 43200)),
        )->plainTextToken;
    }
}
