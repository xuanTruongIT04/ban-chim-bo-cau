<?php

declare(strict_types=1);

namespace App\Application\Order\Actions;

use App\Domain\Order\Entities\CartItem;
use App\Domain\Order\Exceptions\InactiveProductInCartException;
use App\Domain\Order\Repositories\CartRepositoryInterface;
use App\Domain\Product\Exceptions\ProductNotFoundException;
use App\Domain\Product\Repositories\ProductRepositoryInterface;

final class AddToCartAction
{
    public function __construct(
        private readonly CartRepositoryInterface $carts,
        private readonly ProductRepositoryInterface $products,
    ) {}

    public function handle(int $cartId, int $productId, string $quantity): CartItem
    {
        $product = $this->products->findById($productId);

        if ($product === null) {
            throw new ProductNotFoundException($productId);
        }

        if (! $product->isActive) {
            throw new InactiveProductInCartException($product->name);
        }

        $existing = $this->carts->findItemByCartAndProduct($cartId, $productId);

        if ($existing !== null) {
            // D-04: Accumulate quantity when same product added again
            $newQuantity = \bcadd($existing->quantity, $quantity, 3);

            return $this->carts->updateItemQuantity($existing->id, $newQuantity);
        }

        $item = $this->carts->addItem($cartId, $productId, $quantity);

        $this->carts->refreshExpiry($cartId);

        return $item;
    }
}
