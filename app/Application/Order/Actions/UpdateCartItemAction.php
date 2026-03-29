<?php

declare(strict_types=1);

namespace App\Application\Order\Actions;

use App\Domain\Order\Entities\CartItem;
use App\Domain\Order\Repositories\CartRepositoryInterface;

final class UpdateCartItemAction
{
    public function __construct(
        private readonly CartRepositoryInterface $carts,
    ) {}

    public function handle(int $cartItemId, string $quantity): CartItem
    {
        return $this->carts->updateItemQuantity($cartItemId, $quantity);
    }
}
