<?php

describe('JSON Error Envelope', function () {
    it('returns { success, code, message, errors } on validation error (TECH-03)')
        ->todo();

    it('returns 401 envelope on unauthenticated admin route request (TECH-03)')
        ->todo();

    it('returns 404 envelope on non-existent route (TECH-03)')
        ->todo();

    it('never returns HTML on api/* routes even without Accept header (TECH-03)')
        ->todo();
});
