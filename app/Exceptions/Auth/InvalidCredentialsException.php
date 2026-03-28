<?php

declare(strict_types=1);

namespace App\Exceptions\Auth;

use DomainException;

final class InvalidCredentialsException extends DomainException
{
    public function __construct()
    {
        parent::__construct('Thông tin đăng nhập không chính xác.');
    }
}
