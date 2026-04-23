<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\BulkProductRequest;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\ActivityLogger;
use App\Services\RecordLimiter;
use App\Services\WarehouseScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function __construct(
        private readonly WarehouseScope $scope,
        private readonly RecordLimiter $limiter,
        private readonly ActivityLogger $logger,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $warehouseId = $this->scope->effective($request->user(), $request->integer('warehouseId') ?: null);
        $products = Product::query()
            ->forWarehouse($warehouseId)
            ->search($request->string('q')->toString() ?: null)
            ->orderBy('name')
            ->get();

        return ProductResource::collection($products);
    }

    public function show(Request $request, int $id): ProductResource
    {
        $warehouseId = $this->scope->effective($request->user(), null);
        $q = Product::query()->where('id', $id);
        if ($warehouseId) {
            $q->where('warehouse_id', $warehouseId);
        }
        return new ProductResource($q->firstOrFail());
    }

    public function store(StoreProductRequest $request): ProductResource
    {
        $this->limiter->ensureRoom();
        $data = $request->validated();
        $warehouseId = $this->scope->effective($request->user(), $data['warehouseId'] ?? null)
            ?? abort(422, 'المخزن مطلوب');

        $product = Product::create([
            'name' => $data['name'],
            'code' => $data['code'],
            'buy_price' => $data['buyPrice'],
            'sell_price' => $data['sellPrice'],
            'quantity' => $data['quantity'],
            'warehouse_id' => $warehouseId,
        ]);
        $this->logger->log('إضافة منتج', $product->name);
        return new ProductResource($product);
    }

    public function update(UpdateProductRequest $request, int $id): ProductResource
    {
        $warehouseId = $this->scope->effective($request->user(), null);
        $q = Product::query()->where('id', $id);
        if ($warehouseId) {
            $q->where('warehouse_id', $warehouseId);
        }
        $product = $q->firstOrFail();

        $payload = collect($request->validated())
            ->mapWithKeys(fn ($v, string $k) => [match ($k) {
                'buyPrice' => 'buy_price',
                'sellPrice' => 'sell_price',
                default => $k,
            } => $v])
            ->all();

        $product->fill($payload)->save();
        $this->logger->log('تعديل منتج', $product->name);
        return new ProductResource($product);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $warehouseId = $this->scope->effective($request->user(), null);
        $q = Product::query()->where('id', $id);
        if ($warehouseId) {
            $q->where('warehouse_id', $warehouseId);
        }
        $product = $q->firstOrFail();
        $product->delete();
        $this->logger->log('حذف منتج', $product->name);
        return response()->json(['ok' => true]);
    }

    public function bulk(BulkProductRequest $request): JsonResponse
    {
        $data = $request->validated();
        $warehouseId = $this->scope->effective($request->user(), $data['warehouseId'] ?? null)
            ?? abort(422, 'المخزن مطلوب');
        $this->limiter->ensureRoom(count($data['items']));

        $created = 0;
        $updated = 0;
        DB::transaction(function () use ($data, $warehouseId, &$created, &$updated): void {
            foreach ($data['items'] as $row) {
                $product = Product::firstOrNew([
                    'code' => $row['code'],
                    'warehouse_id' => $warehouseId,
                ]);
                $exists = $product->exists;
                $product->fill([
                    'name' => $row['name'],
                    'buy_price' => $row['buyPrice'],
                    'sell_price' => $row['sellPrice'],
                    'quantity' => $row['quantity'],
                    'warehouse_id' => $warehouseId,
                ])->save();
                $exists ? $updated++ : $created++;
            }
        });

        $this->logger->log('استيراد منتجات', "تم إضافة {$created} وتحديث {$updated}");
        return response()->json(['created' => $created, 'updated' => $updated]);
    }
}
