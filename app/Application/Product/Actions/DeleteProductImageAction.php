<?php

declare(strict_types=1);

namespace App\Application\Product\Actions;

use App\Infrastructure\Persistence\Eloquent\Models\ProductImageModel;
use Illuminate\Support\Facades\Storage;

final class DeleteProductImageAction
{
    public function handle(int $imageId): void
    {
        $image = ProductImageModel::findOrFail($imageId);

        Storage::disk('public')->delete($image->path);
        Storage::disk('public')->delete($image->thumbnail_path);

        // Track whether this was the primary image before deleting
        $wasPrimary = $image->is_primary;
        $productId = $image->product_id;

        $image->delete();

        // If this was the primary image, promote the next image by sort order
        if ($wasPrimary) {
            $next = ProductImageModel::where('product_id', $productId)
                ->orderBy('sort_order')
                ->first();
            if ($next) {
                $next->update(['is_primary' => true]);
            }
        }
    }
}
