<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\MovementType;
use App\Exceptions\InsufficientStockException;
use App\Models\Movement;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class InventoryService
{
    public function __construct(
        private readonly RecordLimiter $limiter,
        private readonly ActivityLogger $logger,
    ) {}

    /**
     * Record a stock movement and adjust the product quantity atomically.
     */
    public function record(MovementType $type, string $productCode, int $quantity, float $price, int $warehouseId): Movement
    {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('الكمية يجب أن تكون أكبر من صفر');
        }

        $this->limiter->ensureRoom();

        return DB::transaction(function () use ($type, $productCode, $quantity, $price, $warehouseId): Movement {
            $product = Product::query()
                ->where('code', $productCode)
                ->where('warehouse_id', $warehouseId)
                ->lockForUpdate()
                ->first();

            if (! $product) {
                throw new RuntimeException('المنتج غير موجود في هذا المخزن', 404);
            }

            if ($type === MovementType::Out && $product->quantity < $quantity) {
                throw new InsufficientStockException($product->name);
            }

            $product->quantity = $type === MovementType::In
                ? $product->quantity + $quantity
                : $product->quantity - $quantity;
            $product->save();

            $movement = Movement::create([
                'type' => $type,
                'product_code' => $product->code,
                'product_name' => $product->name,
                'quantity' => $quantity,
                'price' => $price,
                'total' => $quantity * $price,
                'warehouse_id' => $warehouseId,
            ]);

            $this->logger->log($type->label(), "{$quantity} × {$product->name}");

            return $movement;
        });
    }

    /**
     * Reverse a movement: refund/deduct the product's quantity and delete it.
     */
    public function reverse(Movement $movement): void
    {
        DB::transaction(function () use ($movement): void {
            $product = Product::query()
                ->where('code', $movement->product_code)
                ->where('warehouse_id', $movement->warehouse_id)
                ->lockForUpdate()
                ->first();

            if ($product) {
                $delta = $movement->type === MovementType::In ? -$movement->quantity : $movement->quantity;
                $product->quantity += $delta;
                $product->save();
            }

            $movement->delete();
            $this->logger->log('حذف حركة', $movement->product_name);
        });
    }
}
