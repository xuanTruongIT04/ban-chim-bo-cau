<?php

describe('PlaceOrderAction', function () {
    it('prevents concurrent oversell via lockForUpdate (TECH-06)')
        ->todo();

    it('rejects duplicate order with same idempotency key (TECH-06)')
        ->todo();

    it('decrements stock atomically within DB transaction (TECH-06)')
        ->todo();
});
