<?php

declare(strict_types=1);

namespace App\Presentation\Http\Controllers\Admin;

use App\Application\Product\Actions\AdjustStockAction;
use App\Application\Product\Actions\ListStockAdjustmentsAction;
use App\Domain\Product\Enums\AdjustmentType;
use App\Presentation\Http\Requests\AdjustStockRequest;
use App\Presentation\Http\Resources\StockAdjustmentResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class StockAdjustmentController
{
    public function __construct(
        private readonly AdjustStockAction $adjustStockAction,
        private readonly ListStockAdjustmentsAction $listAction,
    ) {}

    public function index(Request $request, int $product): JsonResponse
    {
        $perPage = $request->integer('per_page', 15);
        $page = $request->integer('page', 1);

        $result = $this->listAction->handle($product, $perPage, $page);

        return response()->json([
            'success' => true,
            'data'    => StockAdjustmentResource::collection($result['data']),
            'meta'    => [
                'total'        => $result['total'],
                'per_page'     => $perPage,
                'current_page' => $page,
            ],
        ]);
    }

    public function store(AdjustStockRequest $request, int $product): JsonResponse
    {
        $validated = $request->validated();
        $adminUserId = (int) $request->user()->id;
        $type = AdjustmentType::from($validated['adjustment_type']);

        $adjustment = $this->adjustStockAction->handle(
            productId: $product,
            delta: (string) $validated['delta'],
            type: $type,
            note: $validated['note'] ?? null,
            adminUserId: $adminUserId,
        );

        return response()->json([
            'success' => true,
            'data'    => new StockAdjustmentResource($adjustment),
        ], 201);
    }
}
