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

    public function index(): JsonResponse
    {
        $models = CategoryModel::with('children')->get();

        return response()->json([
            'success' => true,
            'data'    => CategoryResource::collection($models),
        ]);
    }

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

    public function destroy(int $category): JsonResponse
    {
        $this->deleteAction->handle($category);

        return response()->json(null, 204);
    }
}
