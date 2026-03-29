<?php

declare(strict_types=1);

namespace App\Application\Order\Actions;

use App\Domain\Order\Entities\Cart;
use App\Domain\Order\Repositories\CartRepositoryInterface;

final class CreateCartAction
{
    public function __construct(
        private readonly CartRepositoryInterface $carts,
    ) {}

    public function handle(): Cart
    {
        return $this->carts->create();
    }
}
