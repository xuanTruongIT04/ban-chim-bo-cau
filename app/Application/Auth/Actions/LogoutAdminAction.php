<?php

declare(strict_types=1);

namespace App\Application\Auth\Actions;

use Illuminate\Http\Request;

final class LogoutAdminAction
{
    public function handle(Request $request): void
    {
        $request->user()->currentAccessToken()->delete();
    }
}
