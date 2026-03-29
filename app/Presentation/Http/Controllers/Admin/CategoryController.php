<?php

declare(strict_types=1);

namespace App\Presentation\Http\Controllers\Admin;

use App\Application\Product\Actions\CreateCategoryAction;
use App\Application\Product\Actions\DeleteCategoryAction;
use App\Application\Product\Actions\UpdateCategoryAction;
use App\Domain\Product\Exceptions\CategoryNotFoundException;
use App\Infrastructure\Persistence\Eloquent\Models\CategoryModel;
use App\Presentation\Http\Requests\CreateCategoryRequest;
use App\Presentation\Http\Requests\UpdateCategoryRequest;
use App\Presentation\Http\Resources\CategoryResource;
use Illuminate\Http\JsonResponse;

/**
 * @group Admin > Danh mục
 *
 * Quản lý danh mục sản phẩm (max 2 cấp)
 */
final class CategoryController
{
    public function __construct(
        private readonly CreateCategoryAction $createAction,
        private readonly UpdateCategoryAction $updateAction,
        private readonly DeleteCategoryAction $deleteAction,
    ) {}

    /**
     * Danh sách danh mục
     *
     * Trả về toàn bộ danh mục kèm danh mục con (tối đa 2 cấp).
     *
     * @response 200 {
     *   "success": true,
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "Chim bồ câu",
     *       "slug": "chim-bo-cau",
     *       "parent_id": null,
     *       "is_active": true,
     *       "children": []
     *     }
     *   ]
     * }
     */
    public function index(): JsonResponse
    {
        $models = CategoryModel::with('children')->get();

        return response()->json([
            'success' => true,
            'data'    => CategoryResource::collection($models),
        ]);
    }

    /**
     * Tạo danh mục mới
     *
     * Tạo danh mục gốc hoặc danh mục con (tối đa 2 cấp).
     *
     * @bodyParam name string required Tên danh mục. Example: Chim bồ câu sống
     * @bodyParam slug string required Slug URL. Example: chim-bo-cau-song
     * @bodyParam parent_id integer optional ID danh mục cha (null nếu là gốc). Example: 1
     * @bodyParam description string optional Mô tả danh mục. Example: Bồ câu sống bán theo con
     * @bodyParam sort_order integer optional Thứ tự hiển thị (mặc định 0). Example: 1
     * @bodyParam is_active boolean optional Trạng thái hiển thị (mặc định true). Example: true
     *
     * @response 201 {
     *   "success": true,
     *   "data": {
     *     "id": 2,
     *     "name": "Chim bồ câu sống",
     *     "slug": "chim-bo-cau-song",
     *     "parent_id": 1,
     *     "is_active": true,
     *     "children": []
     *   }
     * }
     */
    public function store(CreateCategoryRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $category = $this->createAction->handle(
            name: $validated['name'],
            slug: $validated['slug'],
            parentId: isset($validated['parent_id']) ? (int) $validated['parent_id'] : null,
            description: $validated['description'] ?? null,
            sortOrder: isset($validated['sort_order']) ? (int) $validated['sort_order'] : 0,
            isActive: isset($validated['is_active']) ? (bool) $validated['is_active'] : true,
        );

        $model = CategoryModel::findOrFail($category->id);

        return response()->json([
            'success' => true,
            'data'    => new CategoryResource($model),
        ], 201);
    }

    /**
     * Chi tiết danh mục
     *
     * Trả về thông tin chi tiết một danh mục theo ID.
     *
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "id": 1,
     *     "name": "Chim bồ câu",
     *     "slug": "chim-bo-cau",
     *     "parent_id": null,
     *     "is_active": true,
     *     "children": []
     *   }
     * }
     * @response 404 {"message": "Danh mục không tồn tại."}
     */
    public function show(int $category): JsonResponse
    {
        $model = CategoryModel::find($category);

        if ($model === null) {
            throw new CategoryNotFoundException($category);
        }

        return response()->json([
            'success' => true,
            'data'    => new CategoryResource($model),
        ]);
    }

    /**
     * Cập nhật danh mục
     *
     * Cập nhật thông tin danh mục theo ID.
     *
     * @bodyParam name string required Tên danh mục. Example: Bồ câu thịt
     * @bodyParam slug string required Slug URL. Example: bo-cau-thit
     * @bodyParam parent_id integer optional ID danh mục cha. Example: 1
     * @bodyParam description string optional Mô tả danh mục. Example: Bồ câu làm sẵn bán theo kg
     * @bodyParam sort_order integer optional Thứ tự hiển thị. Example: 2
     * @bodyParam is_active boolean optional Trạng thái hiển thị. Example: true
     *
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "id": 1,
     *     "name": "Bồ câu thịt",
     *     "slug": "bo-cau-thit",
     *     "parent_id": null,
     *     "is_active": true,
     *     "children": []
     *   }
     * }
     * @response 404 {"message": "Danh mục không tồn tại."}
     */
    public function update(UpdateCategoryRequest $request, int $category): JsonResponse
    {
        $validated = $request->validated();

        $updated = $this->updateAction->handle(
            id: $category,
            name: $validated['name'],
            slug: $validated['slug'],
            parentId: isset($validated['parent_id']) ? (int) $validated['parent_id'] : null,
            description: $validated['description'] ?? null,
            sortOrder: isset($validated['sort_order']) ? (int) $validated['sort_order'] : 0,
            isActive: isset($validated['is_active']) ? (bool) $validated['is_active'] : true,
        );

        $model = CategoryModel::findOrFail($updated->id);

        return response()->json([
            'success' => true,
            'data'    => new CategoryResource($model),
        ]);
    }

    /**
     * Xóa danh mục
     *
     * Xóa danh mục theo ID. Không thể xóa danh mục đang có sản phẩm.
     *
     * @response 204 {}
     * @response 404 {"message": "Danh mục không tồn tại."}
     */
    public function destroy(int $category): JsonResponse
    {
        $this->deleteAction->handle($category);

        return response()->json(null, 204);
    }
}
