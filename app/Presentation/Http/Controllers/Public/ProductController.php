<?php

declare(strict_types=1);

namespace App\Presentation\Http\Controllers\Public;

use App\Domain\Product\Exceptions\ProductNotFoundException;
use App\Infrastructure\Persistence\Eloquent\Models\ProductModel;
use App\Presentation\Http\Resources\ProductDetailResource;
use App\Presentation\Http\Resources\ProductResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\QueryBuilder\QueryBuilder;

final class ProductController
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $qb = new QueryBuilder(ProductModel::query()->where('is_active', true), $request);

        $products = $qb
            ->allowedFilters(['category_id'])
            ->defaultSort('name')
            ->allowedSorts(['name', 'price_vnd', 'created_at'])
            ->with(['images' => fn ($q) => $q->where('is_primary', true)])
            ->paginate($request->integer('per_page', 20));

        return ProductResource::collection($products);
    }

    public function show(int $product): JsonResponse
    {
        $model = ProductModel::where('is_active', true)
            ->where('id', $product)
            ->with(['category', 'images'])
            ->first();

        if ($model === null) {
            throw new ProductNotFoundException($product);
        }

        return response()->json([
            'success' => true,
            'data'    => new ProductDetailResource($model),
        ]);
    }
}
