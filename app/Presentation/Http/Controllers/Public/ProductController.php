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
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * @group Public > Sản phẩm
 *
 * Xem danh sách và chi tiết sản phẩm (không cần đăng nhập)
 *
 * @unauthenticated
 */
final class ProductController
{
    /**
     * Danh sách sản phẩm (public)
     *
     * Trả về danh sách sản phẩm đang bán (is_active=true) với phân trang và lọc.
     * Chỉ hiển thị ảnh chính của mỗi sản phẩm.
     *
     * @queryParam filter[category_id] integer Lọc theo danh mục. Example: 1
     * @queryParam sort string Sắp xếp: name, price_vnd, -name, -price_vnd, created_at, -created_at. Example: -created_at
     * @queryParam per_page integer Số sản phẩm mỗi trang (mặc định 20). Example: 20
     * @queryParam page integer Số trang. Example: 1
     *
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "Bồ câu sống",
     *       "price_vnd": 85000,
     *       "unit_type": "con",
     *       "stock_quantity": "50.000",
     *       "primary_image": "https://s3.amazonaws.com/bucket/products/abc123.jpg"
     *     }
     *   ],
     *   "links": {"first": "...", "last": "...", "prev": null, "next": null},
     *   "meta": {"current_page": 1, "last_page": 1, "per_page": 20, "total": 1}
     * }
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $qb = new QueryBuilder(ProductModel::query()->where('is_active', true), $request);

        $products = $qb
            ->allowedFilters('category_id', AllowedFilter::partial('name'))
            ->defaultSort('name')
            ->allowedSorts('name', 'price_vnd', 'created_at')
            ->with(['images' => fn ($q) => $q->where('is_primary', true)])
            ->paginate($request->integer('per_page', 10));

        return ProductResource::collection($products);
    }

    /**
     * Chi tiết sản phẩm (public)
     *
     * Trả về thông tin đầy đủ sản phẩm bao gồm tất cả ảnh, danh mục, và tồn kho hiện tại.
     * Chỉ trả về sản phẩm đang active.
     *
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "id": 1,
     *     "name": "Bồ câu sống",
     *     "description": "Bồ câu ta nuôi tự nhiên",
     *     "price_vnd": 85000,
     *     "unit_type": "con",
     *     "stock_quantity": "50.000",
     *     "category": {"id": 1, "name": "Chim bồ câu"},
     *     "images": [
     *       {"id": 1, "url": "https://s3.amazonaws.com/bucket/products/abc123.jpg", "is_primary": true}
     *     ]
     *   }
     * }
     * @response 404 {"message": "Sản phẩm không tồn tại."}
     */
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
