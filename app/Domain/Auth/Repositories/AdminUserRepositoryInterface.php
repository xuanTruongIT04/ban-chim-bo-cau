<?php

declare(strict_types=1);

namespace App\Domain\Auth\Repositories;

use App\Domain\Auth\Entities\AdminUser;

interface AdminUserRepositoryInterface
{
    public function findByEmail(string $email): ?AdminUser;
    public function createToken(AdminUser $user): string;
}
