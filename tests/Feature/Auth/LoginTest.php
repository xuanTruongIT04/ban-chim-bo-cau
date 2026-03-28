<?php

use App\Infrastructure\Persistence\Eloquent\Models\UserModel;

describe('POST /api/v1/admin/login', function () {
    it('returns a Sanctum token on valid credentials (AUTH-01)')
        ->todo();

    it('returns 401 INVALID_CREDENTIALS envelope on wrong password (AUTH-01)')
        ->todo();

    it('returns 422 VALIDATION_ERROR envelope on missing fields (AUTH-01)')
        ->todo();

    it('created token has non-null expires_at (AUTH-02)')
        ->todo();

    it('validation error message for missing email is in Vietnamese (TECH-04)')
        ->todo();
});
