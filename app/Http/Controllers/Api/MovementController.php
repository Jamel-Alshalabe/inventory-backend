<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\MovementType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Movement\StoreMovementRequest;
use App\Http\Resources\MovementResource;
use App\Models\Movement;
use App\Services\ActivityLogger;
use App\Services\InventoryService;
use App\Services\WarehouseScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class MovementController extends Controller
{
    public function __construct(
        private readonly InventoryService $inventory,
        private readonly WarehouseScope $scope,
        private readonly ActivityLogger $logger,
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

    public function update(Request $request, int $id): MovementResource
    {
        $data = $request->validate([
            'type' => 'required|in:in,out',
            'productCode' => 'required|string',
            'quantity' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
        ]);

        $warehouseId = $this->scope->effective($request->user(), null);
        
        // Find the original movement
        $movement = Movement::query()->where('id', $id);
        if ($warehouseId) {
            $movement->where('warehouse_id', $warehouseId);
        }
        $originalMovement = $movement->firstOrFail();

        // Use transaction to ensure data consistency
        return DB::transaction(function () use ($originalMovement, $data, $warehouseId) {
            // Reverse the original movement
            $this->inventory->reverse($originalMovement);
            
            // Create new movement with updated data
            $newMovement = $this->inventory->record(
                MovementType::from($data['type']),
                $data['productCode'],
                (int) $data['quantity'],
                (float) $data['price'],
                $warehouseId,
            );
            
            // Log the update
            $this->logger->log('تعديل حركة', "تحديث حركة المنتج {$data['productCode']}");
            
            return new MovementResource($newMovement);
        });
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $warehouseId = $this->scope->effective($request->user(), null);
        $q = Movement::query()->where('id', $id);
        if ($warehouseId) {
            $q->where('warehouse_id', $warehouseId);
        }
        $movement = $q->firstOrFail();
        
        // Log the deletion
        $this->logger->log('حذف حركة', "حذف حركة المنتج {$movement->product_name}");
        
        $this->inventory->reverse($movement);
        return response()->json(['ok' => true]);
    }
}
