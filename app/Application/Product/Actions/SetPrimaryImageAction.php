<?php

declare(strict_types=1);

namespace App\Application\Product\Actions;

use App\Infrastructure\Persistence\Eloquent\Models\ProductImageModel;
use Illuminate\Support\Facades\DB;

final class SetPrimaryImageAction
{
    public function handle(int $imageId): ProductImageModel
    {
        return DB::transaction(function () use ($imageId): ProductImageModel {
            $image = ProductImageModel::findOrFail($imageId);

            // Clear all primary flags for this product
            ProductImageModel::where('product_id', $image->product_id)
                ->where('is_primary', true)
                ->update(['is_primary' => false]);

            // Set this image as primary
            $image->is_primary = true;
            $image->save();

            return $image->fresh();
        });
    }
}
