<?php

declare(strict_types=1);

namespace App\Domain\Auth\Entities;

final class AdminUser
{
    public function __construct(
        public readonly int $id,
        public readonly string $email,
        public readonly string $passwordHash,
    ) {}
}
