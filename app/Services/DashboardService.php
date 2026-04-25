<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\MovementType;
use App\Models\Invoice;
use App\Models\Movement;
use App\Models\Product;
use Illuminate\Support\Carbon;

class DashboardService
{
    /**
     * @return array<string, mixed>
     */
    public function summary(?int $warehouseId): array
    {
        $threshold = (int) config('inventory.low_stock_threshold', 5);

        $products = Product::forWarehouse($warehouseId)->get(['quantity', 'sell_price']);
        $totalProducts = $products->count();
        $totalQuantity = (int) $products->sum('quantity');
        $stockValue = (float) $products->reduce(
            fn (float $c, Product $p) => $c + ($p->quantity * $p->sell_price),
            0.0,
        );
        $lowStock = $products->filter(fn (Product $p) => $p->quantity > 0 && $p->quantity <= $threshold)->count();
        $outOfStock = $products->filter(fn (Product $p) => $p->quantity === 0)->count();

        $today = Carbon::today();

        $todayIn = (float) Movement::forWarehouse($warehouseId)
            ->where('type', MovementType::In)
            ->where('created_at', '>=', $today)
            ->sum('total');

        $todayOut = (float) Movement::forWarehouse($warehouseId)
            ->where('type', MovementType::Out)
            ->where('created_at', '>=', $today)
            ->sum('total');

        $totalInvoices = Invoice::forWarehouse($warehouseId)->count();
        $totalSales = (float) Invoice::forWarehouse($warehouseId)->sum('total');

        $recentMovements = Movement::forWarehouse($warehouseId)
            ->orderByDesc('created_at')
            ->limit(8)
            ->get();

        $topProducts = Movement::forWarehouse($warehouseId)
            ->where('type', MovementType::Out)
            ->selectRaw('product_code, product_name, SUM(quantity) as qty')
            ->groupBy('product_code', 'product_name')
            ->orderByDesc('qty')
            ->limit(5)
            ->get()
            ->map(fn ($r) => [
                'productCode' => $r->product_code,
                'productName' => $r->product_name,
                'quantity' => (int) $r->qty,
            ])
            ->all();

        // Get daily movements for the last 7 days
        $dailyMovements = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $dayIn = (float) Movement::forWarehouse($warehouseId)
                ->where('type', MovementType::In)
                ->whereDate('created_at', $date)
                ->sum('total');
                
            $dayOut = (float) Movement::forWarehouse($warehouseId)
                ->where('type', MovementType::Out)
                ->whereDate('created_at', $date)
                ->sum('total');
                
            $dailyMovements[] = [
                'date' => $date->format('Y-m-d'),
                'in' => $dayIn,
                'out' => $dayOut,
            ];
        }

        return [
            'totalProducts' => $totalProducts,
            'totalQuantity' => $totalQuantity,
            'stockValue' => $stockValue,
            'lowStock' => $lowStock,
            'outOfStock' => $outOfStock,
            'totalInvoices' => $totalInvoices,
            'totalSales' => $totalSales,
            'todayIn' => $todayIn,
            'todayOut' => $todayOut,
            'recentMovements' => $recentMovements,
            'topProducts' => $topProducts,
            'dailyMovements' => $dailyMovements,
        ];
    }
}
