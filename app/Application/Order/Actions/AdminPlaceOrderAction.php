<?php

declare(strict_types=1);

namespace App\Application\Order\Actions;

use App\Domain\Order\Entities\Order;
use App\Domain\Order\Enums\OrderStatus;
use App\Domain\Order\Enums\PaymentMethod;
use App\Domain\Order\Enums\PaymentStatus;
use App\Domain\Order\Exceptions\InactiveProductInCartException;
use App\Domain\Order\Repositories\OrderRepositoryInterface;
use App\Domain\Product\Exceptions\InsufficientStockException;
use App\Domain\Product\Exceptions\ProductNotFoundException;
use App\Domain\Product\Repositories\ProductRepositoryInterface;
use App\Infrastructure\Notifications\NewOrderNotification;
use App\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Illuminate\Support\Facades\DB;

final class AdminPlaceOrderAction
{
    public function __construct(
        private readonly ProductRepositoryInterface $products,
        private readonly OrderRepositoryInterface $orders,
    ) {}

    /**
     * @param array<int, array{product_id: int, quantity: string}> $items
     */
    public function handle(
        string $customerName,
        string $customerPhone,
        string $deliveryAddress,
        PaymentMethod $paymentMethod,
        array $items,
        int $adminUserId,
    ): Order {
        $order = DB::transaction(function () use ($customerName, $customerPhone, $deliveryAddress, $paymentMethod, $items, $adminUserId): Order {
            // 1. Sort product IDs ascending to prevent deadlocks (per RESEARCH Pattern 2)
            $sortedProductIds = collect($items)
                ->pluck('product_id')
                ->unique()
                ->sort()
                ->values()
                ->all();

            // 2. Lock all product rows before any check
            $products = [];
            foreach ($sortedProductIds as $productId) {
                $product = $this->products->findByIdForUpdate((int) $productId);
                if ($product === null) {
                    throw new ProductNotFoundException((int) $productId);
                }
                if (! $product->isActive) {
                    throw new InactiveProductInCartException($product->name);
                }
                $products[$productId] = $product;
            }

            // 3. Check stock and decrement for each item
            $orderItems  = [];
            $totalAmount = '0';
            foreach ($items as $item) {
                $productId = (int) $item['product_id'];
                $quantity  = (string) $item['quantity'];
                $product   = $products[$productId];

                $newStock = bcadd($product->stockQuantity, '-' . $quantity, 3);
                if (bccomp($newStock, '0', 3) < 0) {
                    throw new InsufficientStockException($product->stockQuantity, $quantity);
                }
                $this->products->updateStock($productId, $newStock);

                $subtotal = (int) round((float) bcmul((string) $product->priceVnd, $quantity, 3));
                $orderItems[] = [
                    'product_id'   => $productId,
                    'product_name' => $product->name,
                    'price_vnd'    => $product->priceVnd,
                    'quantity'     => $quantity,
                    'subtotal_vnd' => $subtotal,
                ];
                $totalAmount = bcadd($totalAmount, (string) $subtotal, 0);
            }

            // 4. Create order with created_by = adminUserId (per D-23)
            return $this->orders->create([
                'customer_name'    => $customerName,
                'customer_phone'   => $customerPhone,
                'delivery_address' => $deliveryAddress,
                'order_status'     => OrderStatus::ChoXacNhan->value,
                'payment_method'   => $paymentMethod->value,
                'payment_status'   => PaymentStatus::ChuaThanhToan->value,
                'delivery_method'  => null,
                'total_amount'     => $totalAmount,
                'created_by'       => $adminUserId,
            ], $orderItems);
        });

        // Dispatch notification OUTSIDE transaction — afterCommit=true ensures it queues after commit
        $admin = UserModel::first();
        if ($admin !== null) {
            $admin->notify(new NewOrderNotification($order));
        }

        return $order;
    }
}
