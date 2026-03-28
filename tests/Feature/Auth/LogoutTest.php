<?php

describe('POST /api/v1/admin/logout', function () {
    it('deletes the current Sanctum token and returns success (AUTH-04)')
        ->todo();

    it('subsequent request with deleted token returns 401 (AUTH-04)')
        ->todo();
});
