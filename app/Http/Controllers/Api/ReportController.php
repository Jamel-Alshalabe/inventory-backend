<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use App\Services\WarehouseScope;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct(
        private readonly ReportService $reports,
        private readonly WarehouseScope $scope,
    ) {}

    public function sales(Request $request): JsonResponse
    {
        [$wid, $from, $to] = $this->context($request);
        return response()->json($this->reports->sales($wid, $from, $to));
    }

    public function stock(Request $request): JsonResponse
    {
        [$wid] = $this->context($request);
        return response()->json($this->reports->stock($wid));
    }

    public function profit(Request $request): JsonResponse
    {
        [$wid, $from, $to] = $this->context($request);
        return response()->json($this->reports->profit($wid, $from, $to));
    }

    public function movements(Request $request): JsonResponse
    {
        [$wid, $from, $to] = $this->context($request);
        return response()->json($this->reports->movements($wid, $from, $to));
    }

    public function invoices(Request $request): JsonResponse
    {
        [$wid, $from, $to] = $this->context($request);
        return response()->json($this->reports->invoices($wid, $from, $to));
    }

    /**
     * @return array{0: int|null, 1: CarbonImmutable|null, 2: CarbonImmutable|null}
     */
    private function context(Request $request): array
    {
        $wid = $this->scope->effective($request->user(), $request->integer('warehouseId') ?: null);
        $from = $request->filled('from') ? CarbonImmutable::parse((string) $request->query('from'))->startOfDay() : null;
        $to = $request->filled('to') ? CarbonImmutable::parse((string) $request->query('to'))->endOfDay() : null;
        return [$wid, $from, $to];
    }
}
