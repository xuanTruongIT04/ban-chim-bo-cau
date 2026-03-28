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

final class ProductImageController
{
    public function __construct(
        private readonly UploadProductImageAction $uploadAction,
        private readonly SetPrimaryImageAction $setPrimaryAction,
        private readonly DeleteProductImageAction $deleteAction,
    ) {}

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

    public function setPrimary(int $product, int $image): JsonResponse
    {
        $updated = $this->setPrimaryAction->handle($image);

        return response()->json([
            'success' => true,
            'data'    => new ProductImageResource($updated),
        ]);
    }

    public function destroy(int $product, int $image): Response
    {
        $this->deleteAction->handle($image);

        return response()->noContent();
    }
}
