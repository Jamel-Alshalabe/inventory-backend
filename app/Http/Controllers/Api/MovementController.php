<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\MovementType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Movement\StoreMovementRequest;
use App\Http\Resources\MovementResource;
use App\Models\Movement;
use App\Services\InventoryService;
use App\Services\WarehouseScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MovementController extends Controller
{
    public function __construct(
        private readonly InventoryService $inventory,
        private readonly WarehouseScope $scope,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $warehouseId = $this->scope->effective($request->user(), $request->integer('warehouseId') ?: null);
        $movements = Movement::query()
            ->forWarehouse($warehouseId)
            ->orderByDesc('created_at')
            ->limit($request->integer('limit') ?: 500)
            ->get();
        return MovementResource::collection($movements);
    }

    public function store(StoreMovementRequest $request): MovementResource
    {
        $data = $request->validated();
        $warehouseId = $this->scope->effective($request->user(), $data['warehouseId'] ?? null)
            ?? abort(422, 'المخزن مطلوب');

        $movement = $this->inventory->record(
            MovementType::from($data['type']),
            $data['productCode'],
            (int) $data['quantity'],
            (float) $data['price'],
            $warehouseId,
        );
        return new MovementResource($movement);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $warehouseId = $this->scope->effective($request->user(), null);
        $q = Movement::query()->where('id', $id);
        if ($warehouseId) {
            $q->where('warehouse_id', $warehouseId);
        }
        $this->inventory->reverse($q->firstOrFail());
        return response()->json(['ok' => true]);
    }
}
