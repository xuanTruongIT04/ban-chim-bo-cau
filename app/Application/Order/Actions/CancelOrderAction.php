<?php

declare(strict_types=1);

namespace App\Application\Order\Actions;

use App\Domain\Order\Entities\Order;
use App\Domain\Order\Enums\OrderStatus;
use App\Domain\Order\Exceptions\InvalidOrderTransitionException;
use App\Domain\Order\Exceptions\OrderNotFoundException;
use App\Domain\Order\Repositories\OrderRepositoryInterface;
use App\Domain\Product\Repositories\ProductRepositoryInterface;
use Illuminate\Support\Facades\DB;

final class CancelOrderAction
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
        private readonly ProductRepositoryInterface $products,
    ) {}

    /**
     * Cancel an order and restore stock in the same DB transaction.
     *
     * Per D-09: only admin calls this.
     * Per D-10: allowed at any state except hoan_thanh (and already cancelled = huy).
     * Per ORDR-05: stock is restored atomically within the same transaction.
     *
     * Product rows are locked in ascending ID order to prevent deadlocks (same pattern as PlaceOrderAction).
     *
     * @throws OrderNotFoundException if the order does not exist
     * @throws InvalidOrderTransitionException if the order is in hoan_thanh state
     */
    public function handle(int $orderId): Order
    {
        return DB::transaction(function () use ($orderId): Order {
            $order = $this->orders->findById($orderId);

            if ($order === null) {
                throw new OrderNotFoundException($orderId);
            }

            // Already cancelled — idempotent check or reject (Huy -> Huy is not a valid re-cancel)
            if ($order->orderStatus === OrderStatus::Huy || ! $order->orderStatus->isCancellable()) {
                throw new InvalidOrderTransitionException(
                    $order->orderStatus,
                    OrderStatus::Huy
                );
            }

            // Restore stock for each order item — lock product rows to prevent race conditions
            // Sort by productId ascending to prevent deadlocks (consistent lock ordering)
            $sortedItems = collect($order->items)
                ->sortBy('productId')
                ->values();

            foreach ($sortedItems as $item) {
                $product = $this->products->findByIdForUpdate($item->productId);

                if ($product !== null) {
                    $restoredStock = bcadd($product->stockQuantity, $item->quantity, 3);
                    $this->products->updateStock($item->productId, $restoredStock);
                }
                // If product was deleted, skip stock restoration (defensive)
            }

            return $this->orders->updateStatus($orderId, OrderStatus::Huy);
        });
    }
}
