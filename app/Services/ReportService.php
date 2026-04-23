<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\MovementType;
use App\Models\Invoice;
use App\Models\Movement;
use App\Models\Product;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

class ReportService
{
    /**
     * @return array<string, mixed>
     */
    public function sales(?int $warehouseId, ?CarbonImmutable $from, ?CarbonImmutable $to): array
    {
        $rows = $this->scopedMovements($warehouseId, $from, $to)
            ->where('type', MovementType::Out)
            ->orderByDesc('created_at')
            ->get();

        $byDay = $rows->groupBy(fn (Movement $m) => $m->created_at->toDateString())
            ->map(fn ($g, string $date) => [
                'date' => $date,
                'revenue' => (float) $g->sum('total'),
                'quantity' => (int) $g->sum('quantity'),
            ])
            ->sortBy('date')
            ->values();

        return [
            'totalRevenue' => (float) $rows->sum('total'),
            'totalQuantity' => (int) $rows->sum('quantity'),
            'totalTransactions' => $rows->count(),
            'byDay' => $byDay,
            'items' => $rows,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function stock(?int $warehouseId): array
    {
        $products = Product::forWarehouse($warehouseId)->get();
        $totalValue = (float) $products->sum(fn (Product $p) => $p->quantity * $p->sell_price);
        $totalCost = (float) $products->sum(fn (Product $p) => $p->quantity * $p->buy_price);

        return [
            'totalProducts' => $products->count(),
            'totalValue' => $totalValue,
            'totalCost' => $totalCost,
            'estimatedProfit' => $totalValue - $totalCost,
            'items' => $products->map(fn (Product $p) => array_merge($p->toArray(), [
                'stockValue' => $p->quantity * $p->sell_price,
            ])),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function profit(?int $warehouseId, ?CarbonImmutable $from, ?CarbonImmutable $to): array
    {
        $rows = $this->scopedMovements($warehouseId, $from, $to)
            ->where('type', MovementType::Out)
            ->get();

        $buyMap = Product::forWarehouse($warehouseId)
            ->get(['code', 'warehouse_id', 'buy_price'])
            ->keyBy(fn (Product $p) => "{$p->code}|{$p->warehouse_id}");

        $revenue = 0.0;
        $cost = 0.0;
        $items = $rows->map(function (Movement $m) use ($buyMap, &$revenue, &$cost) {
            $buy = (float) ($buyMap["{$m->product_code}|{$m->warehouse_id}"]?->buy_price ?? 0);
            $itemCost = $buy * $m->quantity;
            $revenue += $m->total;
            $cost += $itemCost;
            return array_merge($m->toArray(), [
                'buyPrice' => $buy,
                'cost' => $itemCost,
                'profit' => $m->total - $itemCost,
            ]);
        });

        return [
            'revenue' => $revenue,
            'cost' => $cost,
            'profit' => $revenue - $cost,
            'margin' => $revenue > 0 ? (($revenue - $cost) / $revenue) * 100 : 0,
            'items' => $items,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function movements(?int $warehouseId, ?CarbonImmutable $from, ?CarbonImmutable $to): array
    {
        $rows = $this->scopedMovements($warehouseId, $from, $to)
            ->orderByDesc('created_at')
            ->get();

        return [
            'totalIn' => (int) $rows->where('type', MovementType::In)->sum('quantity'),
            'totalOut' => (int) $rows->where('type', MovementType::Out)->sum('quantity'),
            'totalTransactions' => $rows->count(),
            'items' => $rows,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function invoices(?int $warehouseId, ?CarbonImmutable $from, ?CarbonImmutable $to): array
    {
        $q = Invoice::forWarehouse($warehouseId);
        if ($from) {
            $q->where('created_at', '>=', $from);
        }
        if ($to) {
            $q->where('created_at', '<=', $to);
        }
        $rows = $q->orderByDesc('created_at')->get();

        return [
            'totalInvoices' => $rows->count(),
            'totalSales' => (float) $rows->sum('total'),
            'items' => $rows,
        ];
    }

    private function scopedMovements(?int $warehouseId, ?CarbonImmutable $from, ?CarbonImmutable $to): Builder
    {
        $q = Movement::query()->forWarehouse($warehouseId);
        if ($from) {
            $q->where('created_at', '>=', $from);
        }
        if ($to) {
            $q->where('created_at', '<=', $to);
        }
        return $q;
    }
}
