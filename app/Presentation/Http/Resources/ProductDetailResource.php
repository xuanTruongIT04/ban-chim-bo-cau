<?php

declare(strict_types=1);

namespace App\Presentation\Http\Resources;

use App\Infrastructure\Persistence\Eloquent\Models\ProductModel;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ProductModel
 */
final class ProductDetailResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'name'           => $this->name,
            'description'    => $this->description,
            'price_vnd'      => $this->price_vnd,
            'unit_type'      => $this->unit_type->value,
            'category_id'    => $this->category_id,
            'category'       => new CategoryResource($this->whenLoaded('category')),
            'stock_quantity' => $this->stock_quantity,
            'is_active'      => $this->is_active,
            'images'         => ProductImageResource::collection($this->whenLoaded('images')),
            'created_at'     => $this->created_at,
            'updated_at'     => $this->updated_at,
        ];
    }
}
