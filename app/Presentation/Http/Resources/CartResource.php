<?php

declare(strict_types=1);

namespace App\Presentation\Http\Resources;

use App\Infrastructure\Persistence\Eloquent\Models\CartModel;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin CartModel
 */
final class CartResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Compute total_amount as sum of (quantity * current price) for each item
        $totalAmount = $this->items->reduce(function (int $carry, \App\Infrastructure\Persistence\Eloquent\Models\CartItemModel $item) {
            $subtotal = (int) \bcmul((string) $item->quantity, (string) $item->product->price_vnd, 3);

            return $carry + $subtotal;
        }, 0);

        return [
            'id'           => $this->id,
            'token'        => $this->token,
            'expires_at'   => $this->expires_at,
            'items'        => CartItemResource::collection($this->items),
            'total_amount' => $totalAmount,
        ];
    }
}
