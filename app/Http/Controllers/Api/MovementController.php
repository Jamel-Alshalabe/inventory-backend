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
            ->search($request->query('q'))
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

        $effectiveWarehouseId = $this->scope->effective($request->user(), null);
        
        // Find the original movement
        $q = Movement::query()->where('id', $id);
        if ($effectiveWarehouseId) {
            $q->where('warehouse_id', $effectiveWarehouseId);
        }
        $originalMovement = $q->firstOrFail();

        // Use the original movement's warehouse_id for all product operations
        $warehouseId = $originalMovement->warehouse_id;

        // Use transaction to ensure data consistency
        return DB::transaction(function () use ($originalMovement, $data, $warehouseId) {
            // 1. Reverse the original movement (return stock)
            $product = \App\Models\Product::query()
                ->where('code', $originalMovement->product_code)
                ->where('warehouse_id', $warehouseId)
                ->lockForUpdate()
                ->first();

            if ($product) {
                $delta = $originalMovement->type === MovementType::In ? -$originalMovement->quantity : $originalMovement->quantity;
                $product->quantity += $delta;
                $product->save();
            }
            
            // 2. Apply new movement data to product stock
            $newProduct = \App\Models\Product::query()
                ->where('code', $data['productCode'])
                ->where('warehouse_id', $warehouseId)
                ->lockForUpdate()
                ->first();

            if (!$newProduct) {
                throw new \RuntimeException('المنتج غير موجود في هذا المخزن', 404);
            }

            $type = MovementType::from($data['type']);
            if ($type === MovementType::Out && $newProduct->quantity < (int)$data['quantity']) {
                throw new \App\Exceptions\InsufficientStockException($newProduct->name);
            }

            $newProduct->quantity = ($type === MovementType::In)
                ? $newProduct->quantity + (int)$data['quantity']
                : $newProduct->quantity - (int)$data['quantity'];
            $newProduct->save();

            // 3. Update the movement record instead of creating new one if possible, 
            // but the original controller logic creates a new one. Let's update the existing one.
            $originalMovement->update([
                'type' => $type,
                'product_code' => $newProduct->code,
                'product_name' => $newProduct->name,
                'quantity' => (int)$data['quantity'],
                'price' => (float)$data['price'],
                'total' => (int)$data['quantity'] * (float)$data['price'],
                'warehouse_id' => $warehouseId,
            ]);
            
            // Log the update
            $this->logger->log('تعديل حركة', "تحديث حركة المنتج {$data['productCode']}");
            
            return new MovementResource($originalMovement);
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
