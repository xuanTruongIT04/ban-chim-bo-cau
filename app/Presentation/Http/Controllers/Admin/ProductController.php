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

/**
 * @group Admin > Sản phẩm
 *
 * CRUD sản phẩm và toggle trạng thái active
 */
final class ProductController
{
    public function __construct(
        private readonly CreateProductAction $createAction,
        private readonly UpdateProductAction $updateAction,
        private readonly DeleteProductAction $deleteAction,
        private readonly ToggleProductActiveAction $toggleActiveAction,
    ) {}

    /**
     * Danh sách sản phẩm (admin)
     *
     * Trả về danh sách tất cả sản phẩm có phân trang, bao gồm sản phẩm không active.
     *
     * @queryParam page integer Số trang. Example: 1
     * @queryParam per_page integer Số sản phẩm mỗi trang (mặc định 20). Example: 20
     *
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "Bồ câu sống",
     *       "price_vnd": 85000,
     *       "unit_type": "con",
     *       "stock_quantity": "50.000",
     *       "is_active": true
     *     }
     *   ],
     *   "links": {"first": "...", "last": "...", "prev": null, "next": null},
     *   "meta": {"current_page": 1, "last_page": 1, "per_page": 20, "total": 1}
     * }
     */
    public function index(): AnonymousResourceCollection
    {
        $products = ProductModel::with(['category', 'images'])->paginate(20);

        return ProductResource::collection($products);
    }

    /**
     * Tạo sản phẩm mới
     *
     * Tạo sản phẩm với thông tin cơ bản và tồn kho ban đầu.
     *
     * @bodyParam name string required Tên sản phẩm. Example: Bồ câu sống
     * @bodyParam description string optional Mô tả sản phẩm. Example: Bồ câu ta nuôi tự nhiên, bán theo cặp hoặc lẻ
     * @bodyParam price_vnd integer required Giá bán (VNĐ). Example: 85000
     * @bodyParam unit_type string required Đơn vị bán: con hoặc kg. Example: con
     * @bodyParam category_id integer required ID danh mục. Example: 1
     * @bodyParam stock_quantity numeric optional Tồn kho ban đầu (mặc định 0). Example: 50
     * @bodyParam is_active boolean optional Trạng thái hiển thị (mặc định true). Example: true
     *
     * @response 201 {
     *   "success": true,
     *   "data": {
     *     "id": 1,
     *     "name": "Bồ câu sống",
     *     "price_vnd": 85000,
     *     "unit_type": "con",
     *     "stock_quantity": "50.000",
     *     "is_active": true,
     *     "category": {"id": 1, "name": "Chim bồ câu"},
     *     "images": []
     *   }
     * }
     */
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

    /**
     * Chi tiết sản phẩm (admin)
     *
     * Trả về thông tin đầy đủ của sản phẩm bao gồm tất cả ảnh và thông tin tồn kho.
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
     *     "is_active": true,
     *     "category": {"id": 1, "name": "Chim bồ câu"},
     *     "images": []
     *   }
     * }
     * @response 404 {"message": "Sản phẩm không tồn tại."}
     */
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

    /**
     * Cập nhật sản phẩm
     *
     * Cập nhật thông tin sản phẩm. Tất cả trường đều tùy chọn ngoại trừ name.
     *
     * @bodyParam name string required Tên sản phẩm. Example: Bồ câu sống (cặp)
     * @bodyParam description string optional Mô tả sản phẩm. Example: Bồ câu ta nuôi tự nhiên, bán theo cặp
     * @bodyParam price_vnd integer optional Giá bán (VNĐ). Example: 160000
     * @bodyParam unit_type string optional Đơn vị bán: con hoặc kg. Example: con
     * @bodyParam category_id integer optional ID danh mục. Example: 1
     * @bodyParam is_active boolean optional Trạng thái hiển thị. Example: true
     *
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "id": 1,
     *     "name": "Bồ câu sống (cặp)",
     *     "price_vnd": 160000,
     *     "unit_type": "con",
     *     "is_active": true
     *   }
     * }
     * @response 404 {"message": "Sản phẩm không tồn tại."}
     */
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

    /**
     * Xóa sản phẩm
     *
     * Xóa sản phẩm và tất cả ảnh liên quan. Không thể hoàn tác.
     *
     * @response 204 {}
     * @response 404 {"message": "Sản phẩm không tồn tại."}
     */
    public function destroy(int $product): JsonResponse
    {
        $this->deleteAction->handle($product);

        return response()->json(null, 204);
    }

    /**
     * Bật/tắt trạng thái sản phẩm
     *
     * Toggle trạng thái hiển thị sản phẩm (active/inactive).
     * Sản phẩm inactive sẽ không hiện trong danh sách public.
     *
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "id": 1,
     *     "name": "Bồ câu sống",
     *     "is_active": false
     *   }
     * }
     * @response 404 {"message": "Sản phẩm không tồn tại."}
     */
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
