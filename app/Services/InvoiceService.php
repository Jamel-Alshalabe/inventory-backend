<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\MovementType;
use App\Exceptions\InsufficientStockException;
use App\Models\Invoice;
use App\Models\Movement;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class InvoiceService
{
    public function __construct(
        private readonly RecordLimiter $limiter,
        private readonly ActivityLogger $logger,
    ) {}

    /**
     * Create an invoice atomically: validate stock, deduct stock, record
     * outgoing movements per line item, and persist the invoice header.
     *
     * @param  array<int, array{productCode: string, quantity: int, price?: float|null}>  $items
     */
    public function create(string $customerName, array $items, int $warehouseId): Invoice
    {
        if ($items === []) {
            throw new RuntimeException('الأصناف مطلوبة', 422);
        }

        $this->limiter->ensureRoom();

        return DB::transaction(function () use ($customerName, $items, $warehouseId): Invoice {
            $fullItems = [];
            $total = 0.0;

            foreach ($items as $line) {
                $product = Product::query()
                    ->where('code', $line['productCode'])
                    ->where('warehouse_id', $warehouseId)
                    ->lockForUpdate()
                    ->first();

                if (! $product) {
                    throw new RuntimeException("المنتج {$line['productCode']} غير موجود", 422);
                }

                $qty = (int) $line['quantity'];
                if ($qty <= 0 || $product->quantity < $qty) {
                    throw new InsufficientStockException($product->name);
                }

                $price = isset($line['price']) ? (float) $line['price'] : (float) $product->sell_price;
                $lineTotal = $qty * $price;

                $product->quantity -= $qty;
                $product->save();

                Movement::create([
                    'type' => MovementType::Out,
                    'product_code' => $product->code,
                    'product_name' => $product->name,
                    'quantity' => $qty,
                    'price' => $price,
                    'total' => $lineTotal,
                    'warehouse_id' => $warehouseId,
                ]);

                $fullItems[] = [
                    'productCode' => $product->code,
                    'productName' => $product->name,
                    'quantity' => $qty,
                    'price' => $price,
                    'total' => $lineTotal,
                ];
                $total += $lineTotal;
            }

            $number = 'INV-'.substr((string) (int) (microtime(true) * 1000), -8);

            $invoice = Invoice::create([
                'invoice_number' => $number,
                'customer_name' => $customerName,
                'items' => $fullItems,
                'total' => $total,
                'status' => 'paid',
                'warehouse_id' => $warehouseId,
            ]);

            $this->logger->log('إنشاء فاتورة', "{$number} - {$customerName}");

            return $invoice;
        });
    }
}
