<?php

declare(strict_types=1);

namespace App\Presentation\Http\Resources;

use App\Infrastructure\Persistence\Eloquent\Models\ProductModel;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * @mixin ProductModel
 */
final class ProductResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'name'          => $this->name,
            'description'   => $this->description,
            'price_vnd'     => $this->price_vnd,
            'unit_type'     => $this->unit_type->value,
            'category_id'   => $this->category_id,
            'stock_quantity' => $this->stock_quantity,
            'is_active'     => $this->is_active,
            'primary_image' => $this->whenLoaded('images', function () {
                $primary = $this->images->firstWhere('is_primary', true);

                if ($primary === null) {
                    return null;
                }

                return [
                    'url'           => Storage::disk('s3')->url($primary->path),
                    'thumbnail_url' => Storage::disk('s3')->url($primary->thumbnail_path),
                ];
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
