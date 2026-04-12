<?php

declare(strict_types=1);

namespace App\Application\Order\Actions;

use App\Domain\Order\Entities\Cart;
use App\Domain\Order\Entities\Order;
use App\Domain\Order\Enums\OrderStatus;
use App\Domain\Order\Enums\PaymentMethod;
use App\Domain\Order\Enums\PaymentStatus;
use App\Domain\Order\Exceptions\InactiveProductInCartException;
use App\Domain\Order\Repositories\CartRepositoryInterface;
use App\Domain\Order\Repositories\OrderRepositoryInterface;
use App\Domain\Product\Exceptions\InsufficientStockException;
use App\Domain\Product\Exceptions\ProductNotFoundException;
use App\Domain\Product\Repositories\ProductRepositoryInterface;
use App\Infrastructure\Notifications\NewOrderNotification;
use App\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Illuminate\Support\Facades\DB;

final class PlaceOrderAction
{
    public function __construct(
        private readonly ProductRepositoryInterface $products,
        private readonly OrderRepositoryInterface $orders,
        private readonly CartRepositoryInterface $carts,
    ) {}

    public function handle(
        Cart $cart,
        string $customerName,
        string $customerPhone,
        string $deliveryAddress,
        PaymentMethod $paymentMethod,
    ): Order {
        $order = DB::transaction(function () use ($cart, $customerName, $customerPhone, $deliveryAddress, $paymentMethod): Order {
            // 1. Sort product IDs ascending to prevent deadlocks
            $sortedProductIds = collect($cart->items)
                ->pluck('productId')
                ->unique()
                ->sort()
                ->values()
                ->all();

            // 2. Lock all product rows before any check
            $products = [];
            foreach ($sortedProductIds as $productId) {
                $product = $this->products->findByIdForUpdate($productId);
                if ($product === null) {
                    throw new ProductNotFoundException($productId);
                }
                if (! $product->isActive) {
                    throw new InactiveProductInCartException($product->name);
                }
                $products[$productId] = $product;
            }

            // 3. Check stock and decrement for each cart item
            $orderItems  = [];
            $totalAmount = '0';
            foreach ($cart->items as $item) {
                $product  = $products[$item->productId];
                $newStock = \bcadd($product->stockQuantity, '-' . $item->quantity, 3);
                if (\bccomp($newStock, '0', 3) < 0) {
                    throw new InsufficientStockException($product->stockQuantity, $item->quantity);
                }
                $this->products->updateStock($item->productId, $newStock);

                $subtotal = (int) round((float) \bcmul((string) $product->priceVnd, $item->quantity, 3));
                $orderItems[] = [
                    'product_id'   => $item->productId,
                    'product_name' => $product->name,
                    'price_vnd'    => $product->priceVnd,
                    'quantity'     => $item->quantity,
                    'subtotal_vnd' => $subtotal,
                ];
                $totalAmount = \bcadd($totalAmount, (string) $subtotal, 0);
            }

            // 4. Create order
            $order = $this->orders->create([
                'customer_name'    => $customerName,
                'customer_phone'   => $customerPhone,
                'delivery_address' => $deliveryAddress,
                'order_status'     => OrderStatus::ChoXacNhan->value,
                'payment_method'   => $paymentMethod->value,
                'payment_status'   => PaymentStatus::ChuaThanhToan->value,
                'delivery_method'  => null,
                'total_amount'     => $totalAmount,
                'created_by'       => null, // customer order
            ], $orderItems);

            // 5. Delete cart after successful order
            $this->carts->delete($cart->id);

            return $order;
        });

        // Dispatch notification OUTSIDE transaction — afterCommit=true ensures it queues after commit
        $admin = UserModel::first();
        if ($admin !== null) {
            $admin->notify(new NewOrderNotification($order));
        }

        return $order;
    }
}
