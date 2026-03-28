<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Mappers;

use App\Domain\Auth\Entities\AdminUser;
use App\Infrastructure\Persistence\Eloquent\Models\UserModel;

final class UserMapper
{
    public static function toDomain(UserModel $model): AdminUser
    {
        return new AdminUser(
            id: $model->id,
            email: $model->email,
            passwordHash: $model->password,
        );
    }
}
