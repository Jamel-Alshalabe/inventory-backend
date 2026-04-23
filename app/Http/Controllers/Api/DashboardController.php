<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use App\Services\WarehouseScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboard,
        private readonly WarehouseScope $scope,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $warehouseId = $this->scope->effective(
            $request->user(),
            $request->integer('warehouseId') ?: null,
        );
        return response()->json($this->dashboard->summary($warehouseId));
    }
}
