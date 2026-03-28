<?php

declare(strict_types=1);

namespace App\Presentation\Http\Controllers\Admin;

use App\Application\Product\Actions\CreateProductAction;
use App\Application\Product\Actions\DeleteProductAction;
use App\Application\Product\Actions\ToggleProductActiveAction;
use App\Application\Product\Actions\UpdateProductAction;
use App\Domain\Product\Exceptions\ProductNotFoundException;
use App\Infrastructure\Persistence\Eloquent\Models\ProductModel;
use App\Presentation\Http\Requests\CreateProductRequest;
use App\Presentation\Http\Requests\UpdateProductRequest;
use App\Presentation\Http\Resources\ProductDetailResource;
use App\Presentation\Http\Resources\ProductResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class ProductController
{
    public function __construct(
        private readonly CreateProductAction $createAction,
        private readonly UpdateProductAction $updateAction,
        private readonly DeleteProductAction $deleteAction,
        private readonly ToggleProductActiveAction $toggleActiveAction,
    ) {}

    public function index(): AnonymousResourceCollection
    {
        $products = ProductModel::with(['category', 'images'])->paginate(20);

        return ProductResource::collection($products);
    }

    public function store(CreateProductRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $product = $this->createAction->handle(
            name: $validated['name'],
            description: $validated['description'] ?? null,
            priceVnd: (int) $validated['price_vnd'],
            unitType: $validated['unit_type'],
            categoryId: (int) $validated['category_id'],
            stockQuantity: isset($validated['stock_quantity']) ? (string) $validated['stock_quantity'] : '0.000',
            isActive: isset($validated['is_active']) ? (bool) $validated['is_active'] : true,
        );

        $model = ProductModel::with(['category', 'images'])->findOrFail($product->id);

        return response()->json([
            'success' => true,
            'data'    => new ProductResource($model),
        ], 201);
    }

    public function show(int $product): JsonResponse
    {
        $model = ProductModel::with(['category', 'images'])->find($product);

        if ($model === null) {
            throw new ProductNotFoundException($product);
        }

        return response()->json([
            'success' => true,
            'data'    => new ProductDetailResource($model),
        ]);
    }

    public function update(UpdateProductRequest $request, int $product): JsonResponse
    {
        $validated = $request->validated();

        // Load existing product to fill missing optional fields
        $existing = ProductModel::findOrFail($product);

        $updated = $this->updateAction->handle(
            id: $product,
            name: $validated['name'],
            description: $validated['description'] ?? $existing->description,
            priceVnd: isset($validated['price_vnd']) ? (int) $validated['price_vnd'] : $existing->price_vnd,
            unitType: $validated['unit_type'] ?? $existing->unit_type->value,
            categoryId: isset($validated['category_id']) ? (int) $validated['category_id'] : $existing->category_id,
            isActive: isset($validated['is_active']) ? (bool) $validated['is_active'] : $existing->is_active,
        );

        $model = ProductModel::with(['category', 'images'])->findOrFail($updated->id);

        return response()->json([
            'success' => true,
            'data'    => new ProductResource($model),
        ]);
    }

    public function destroy(int $product): JsonResponse
    {
        $this->deleteAction->handle($product);

        return response()->json(null, 204);
    }

    public function toggleActive(int $product): JsonResponse
    {
        $updated = $this->toggleActiveAction->handle($product);

        $model = ProductModel::with(['category', 'images'])->findOrFail($updated->id);

        return response()->json([
            'success' => true,
            'data'    => new ProductResource($model),
        ]);
    }
}
