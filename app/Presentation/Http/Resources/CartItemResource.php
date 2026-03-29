<?php

declare(strict_types=1);

namespace App\Presentation\Http\Resources;

use App\Infrastructure\Persistence\Eloquent\Models\CartItemModel;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin CartItemModel
 */
final class CartItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $product  = $this->product;
        $quantity = (string) $this->quantity;
        $subtotal = (int) bcmul($quantity, (string) $product->price_vnd, 3);

        return [
            'id'                => $this->id,
            'product_id'        => $this->product_id,
            'product_name'      => $product->name,
            'product_price_vnd' => $product->price_vnd,
            'quantity'          => $quantity,
            'subtotal'          => $subtotal,
            'is_available'      => $product->is_active,
        ];
    }
}
