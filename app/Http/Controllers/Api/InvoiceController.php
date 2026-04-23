<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Invoice\StoreInvoiceRequest;
use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Services\InvoiceService;
use App\Services\WarehouseScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class InvoiceController extends Controller
{
    public function __construct(
        private readonly InvoiceService $invoices,
        private readonly WarehouseScope $scope,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $warehouseId = $this->scope->effective($request->user(), $request->integer('warehouseId') ?: null);
        $rows = Invoice::query()->forWarehouse($warehouseId)->orderByDesc('created_at')->get();
        return InvoiceResource::collection($rows);
    }

    public function show(Request $request, int $id): InvoiceResource
    {
        $warehouseId = $this->scope->effective($request->user(), null);
        $q = Invoice::query()->where('id', $id);
        if ($warehouseId) {
            $q->where('warehouse_id', $warehouseId);
        }
        return new InvoiceResource($q->firstOrFail());
    }

    public function store(StoreInvoiceRequest $request): InvoiceResource
    {
        $data = $request->validated();
        $warehouseId = $this->scope->effective($request->user(), $data['warehouseId'] ?? null)
            ?? abort(422, 'المخزن مطلوب');

        $invoice = $this->invoices->create(
            $data['customerName'],
            $data['items'],
            $warehouseId,
        );
        return new InvoiceResource($invoice);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $warehouseId = $this->scope->effective($request->user(), null);
        $q = Invoice::query()->where('id', $id);
        if ($warehouseId) {
            $q->where('warehouse_id', $warehouseId);
        }
        $invoice = $q->firstOrFail();
        $invoice->delete();
        return response()->json(['ok' => true]);
    }
}
