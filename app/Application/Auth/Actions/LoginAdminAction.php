<?php

declare(strict_types=1);

namespace App\Application\Auth\Actions;

use App\Domain\Auth\Repositories\AdminUserRepositoryInterface;
use App\Exceptions\Auth\InvalidCredentialsException;
use Illuminate\Support\Facades\Hash;

final class LoginAdminAction
{
    public function __construct(
        private readonly AdminUserRepositoryInterface $adminUsers,
    ) {}

    public function handle(string $email, string $password): string
    {
        $user = $this->adminUsers->findByEmail($email);

        if ($user === null || ! Hash::check($password, $user->passwordHash)) {
            throw new InvalidCredentialsException();
        }

        return $this->adminUsers->createToken($user);
    }
}
