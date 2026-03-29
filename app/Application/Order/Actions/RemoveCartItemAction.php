<?php

declare(strict_types=1);

namespace App\Application\Order\Actions;

use App\Domain\Order\Repositories\CartRepositoryInterface;

final class RemoveCartItemAction
{
    public function __construct(
        private readonly CartRepositoryInterface $carts,
    ) {}

    public function handle(int $cartItemId): void
    {
        $this->carts->removeItem($cartItemId);
    }
}
