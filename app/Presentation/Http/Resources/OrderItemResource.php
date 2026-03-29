<?php

declare(strict_types=1);

namespace App\Presentation\Http\Resources;

use App\Domain\Order\Entities\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin OrderItem
 */
final class OrderItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var OrderItem $item */
        $item = $this->resource;

        return [
            'id'           => $item->id,
            'product_id'   => $item->productId,
            'product_name' => $item->productName,
            'price_vnd'    => $item->priceVnd,
            'quantity'     => $item->quantity,
            'subtotal_vnd' => $item->subtotalVnd,
        ];
    }
}
