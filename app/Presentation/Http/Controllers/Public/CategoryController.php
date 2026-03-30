<?php

declare(strict_types=1);

namespace App\Presentation\Http\Controllers\Public;

use App\Infrastructure\Persistence\Eloquent\Models\CategoryModel;
use App\Presentation\Http\Resources\CategoryResource;
use Illuminate\Http\JsonResponse;

/**
 * @group Public > Danh mục
 *
 * Xem danh sách danh mục sản phẩm (không cần đăng nhập)
 *
 * @unauthenticated
 */
final class CategoryController
{
    /**
     * Danh sách danh mục (public)
     *
     * Trả về danh mục đang active kèm danh mục con (tối đa 2 cấp).
     *
     * @response 200 {
     *   "success": true,
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "Chim bồ câu",
     *       "slug": "chim-bo-cau",
     *       "parent_id": null,
     *       "children": []
     *     }
     *   ]
     * }
     */
    public function index(): JsonResponse
    {
        $categories = CategoryModel::with('children')
            ->where('is_active', true)
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => CategoryResource::collection($categories),
        ]);
    }
}
