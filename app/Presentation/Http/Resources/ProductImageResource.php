<?php

declare(strict_types=1);

namespace App\Presentation\Http\Resources;

use App\Infrastructure\Persistence\Eloquent\Models\ProductImageModel;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * @mixin ProductImageModel
 */
final class ProductImageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'url'           => Storage::disk('public')->url($this->path),
            'thumbnail_url' => Storage::disk('public')->url($this->thumbnail_path),
            'is_primary'    => $this->is_primary,
            'sort_order'    => $this->sort_order,
        ];
    }
}
