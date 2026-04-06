<?php

declare(strict_types=1);

namespace App\Application\Product\Actions;

use App\Domain\Product\Exceptions\ProductNotFoundException;
use App\Domain\Product\Repositories\ProductRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\Models\ProductImageModel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

final class UploadProductImageAction
{
    public function __construct(
        private readonly ProductRepositoryInterface $products,
    ) {}

    public function handle(int $productId, UploadedFile $file, bool $isPrimary = false): ProductImageModel
    {
        $product = $this->products->findById($productId);
        if ($product === null) {
            throw new ProductNotFoundException($productId);
        }

        $filename = Str::uuid() . '.jpg';
        $originalPath = "products/{$productId}/{$filename}";
        $thumbPath = "products/{$productId}/thumb_{$filename}";

        // Intervention Image 3.x API — read() not make()
        $manager = new ImageManager(new Driver());
        $image = $manager->read($file->getRealPath());

        // Upload original (resized to max 1200px wide for bandwidth)
        $resized = $image->scale(width: 1200);
        Storage::disk('public')->put($originalPath, $resized->toJpeg(quality: 85)->toString());

        // Generate and upload thumbnail (400px wide per research)
        $thumb = $image->scale(width: 400);
        Storage::disk('public')->put($thumbPath, $thumb->toJpeg(quality: 75)->toString());

        // If this is the first image for this product, auto-set as primary
        $isFirstImage = ProductImageModel::where('product_id', $productId)->count() === 0;

        // If this is set as primary, clear other primaries first
        if ($isPrimary) {
            ProductImageModel::where('product_id', $productId)
                ->where('is_primary', true)
                ->update(['is_primary' => false]);
        }

        $maxSort = ProductImageModel::where('product_id', $productId)->max('sort_order') ?? -1;

        return ProductImageModel::create([
            'product_id'     => $productId,
            'path'           => $originalPath,
            'thumbnail_path' => $thumbPath,
            'is_primary'     => $isPrimary || $isFirstImage,
            'sort_order'     => $maxSort + 1,
        ]);
    }
}
