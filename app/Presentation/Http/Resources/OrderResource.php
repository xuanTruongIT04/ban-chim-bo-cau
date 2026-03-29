<?php

declare(strict_types=1);

namespace App\Presentation\Http\Resources;

use App\Domain\Order\Entities\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Order
 */
final class OrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Order $order */
        $order = $this->resource;

        return [
            'id'                    => $order->id,
            'customer_name'         => $order->customerName,
            'customer_phone'        => $order->customerPhone,
            'delivery_address'      => $order->deliveryAddress,
            'order_status'          => $order->orderStatus->value,
            'order_status_label'    => $order->orderStatus->label(),
            'payment_method'        => $order->paymentMethod->value,
            'payment_method_label'  => $order->paymentMethod->label(),
            'payment_status'        => $order->paymentStatus->value,
            'payment_status_label'  => $order->paymentStatus->label(),
            'delivery_method'       => $order->deliveryMethod?->value,
            'delivery_method_label' => $order->deliveryMethod?->label(),
            'total_amount'          => $order->totalAmount,
            'created_by'            => $order->createdBy,
            'items'                 => OrderItemResource::collection($order->items),
            'created_at'            => $order->createdAt,
            'updated_at'            => $order->updatedAt,
        ];
    }
}
