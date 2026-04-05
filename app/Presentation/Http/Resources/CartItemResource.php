<?php

declare(strict_types=1);

namespace App\Presentation\Http\Resources;

use App\Infrastructure\Persistence\Eloquent\Models\CartItemModel;
use App\Infrastructure\Persistence\Eloquent\Models\ProductModel;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

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
            'primary_image'     => $this->primaryImage($product),
        ];
    }

    /** @return array{url: string, thumbnail_url: string}|null */
    private function primaryImage(ProductModel $product): ?array
    {
        if (! $product->relationLoaded('images')) {
            return null;
        }

        $primary = $product->images->firstWhere('is_primary', true);

        if ($primary === null) {
            return null;
        }

        return [
            'url'           => Storage::disk('s3')->url($primary->path),
            'thumbnail_url' => Storage::disk('s3')->url($primary->thumbnail_path),
        ];
    }
}
