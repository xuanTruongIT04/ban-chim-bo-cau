<?php

declare(strict_types=1);

namespace App\Presentation\Http\Controllers\Admin;

use App\Application\Product\Actions\DeleteProductImageAction;
use App\Application\Product\Actions\SetPrimaryImageAction;
use App\Application\Product\Actions\UploadProductImageAction;
use App\Presentation\Http\Requests\UploadProductImageRequest;
use App\Presentation\Http\Resources\ProductImageResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

/**
 * @group Admin > Ảnh sản phẩm
 *
 * Upload, đặt ảnh chính, xóa ảnh sản phẩm (lưu trên S3)
 */
final class ProductImageController
{
    public function __construct(
        private readonly UploadProductImageAction $uploadAction,
        private readonly SetPrimaryImageAction $setPrimaryAction,
        private readonly DeleteProductImageAction $deleteAction,
    ) {}

    /**
     * Upload ảnh sản phẩm
     *
     * Upload ảnh mới cho sản phẩm. Ảnh được lưu trên S3 và resize tự động.
     * Nếu sản phẩm chưa có ảnh nào, ảnh đầu tiên sẽ tự động là ảnh chính.
     *
     * @bodyParam image file required File ảnh (JPEG/PNG/WebP, tối đa 5MB).
     * @bodyParam is_primary boolean optional Đặt làm ảnh chính (mặc định false). Example: false
     *
     * @response 201 {
     *   "success": true,
     *   "data": {
     *     "id": 3,
     *     "product_id": 1,
     *     "url": "https://s3.amazonaws.com/bucket/products/abc123.jpg",
     *     "is_primary": false
     *   }
     * }
     */
    public function store(UploadProductImageRequest $request, int $product): JsonResponse
    {
        $image = $this->uploadAction->handle(
            productId: $product,
            file: $request->file('image'),
            isPrimary: $request->boolean('is_primary', false),
        );

        return response()->json([
            'success' => true,
            'data'    => new ProductImageResource($image),
        ], 201);
    }

    /**
     * Đặt ảnh chính
     *
     * Đặt ảnh này làm ảnh đại diện chính cho sản phẩm.
     * Ảnh chính cũ sẽ tự động chuyển thành ảnh phụ.
     *
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "id": 3,
     *     "product_id": 1,
     *     "url": "https://s3.amazonaws.com/bucket/products/abc123.jpg",
     *     "is_primary": true
     *   }
     * }
     * @response 404 {"message": "Ảnh không tồn tại."}
     */
    public function setPrimary(int $product, int $image): JsonResponse
    {
        $updated = $this->setPrimaryAction->handle($image);

        return response()->json([
            'success' => true,
            'data'    => new ProductImageResource($updated),
        ]);
    }

    /**
     * Xóa ảnh sản phẩm
     *
     * Xóa ảnh khỏi S3 và cơ sở dữ liệu. Nếu ảnh bị xóa là ảnh chính,
     * ảnh tiếp theo sẽ tự động được đặt làm ảnh chính.
     *
     * @response 204 {}
     * @response 404 {"message": "Ảnh không tồn tại."}
     */
    public function destroy(int $product, int $image): Response
    {
        $this->deleteAction->handle($image);

        return response()->noContent();
    }
}
