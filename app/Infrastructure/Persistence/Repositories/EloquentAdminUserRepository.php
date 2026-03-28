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

    /**
     * @return array{token: string, expires_at: string}
     */
    public function createToken(AdminUser $user): array
    {
        $model = UserModel::findOrFail($user->id);
        $expiresAt = now()->addMinutes((int) config('sanctum.expiration', 43200));

        $newAccessToken = $model->createToken(
            name: 'admin-session',
            abilities: ['*'],
            expiresAt: $expiresAt,
        );

        return [
            'token'      => $newAccessToken->plainTextToken,
            'expires_at' => $expiresAt->toIso8601String(),
        ];
    }
}
