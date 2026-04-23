<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Warehouse\StoreWarehouseRequest;
use App\Http\Resources\WarehouseResource;
use App\Models\Warehouse;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class WarehouseController extends Controller
{
    public function __construct(private readonly ActivityLogger $logger)
    {
    }

    public function index(): AnonymousResourceCollection
    {
        return WarehouseResource::collection(Warehouse::query()->orderBy('name')->get());
    }

    public function store(StoreWarehouseRequest $request): WarehouseResource
    {
        $w = Warehouse::create($request->validated());
        $this->logger->log('إضافة مخزن', $w->name);
        return new WarehouseResource($w);
    }

    public function update(Request $request, int $id): WarehouseResource
    {
        $w = Warehouse::findOrFail($id);
        $data = $request->validate(['name' => ['required', 'string', 'max:191']]);
        $w->update($data);
        $this->logger->log('تعديل مخزن', $w->name);
        return new WarehouseResource($w);
    }

    public function destroy(int $id): JsonResponse
    {
        $w = Warehouse::findOrFail($id);
        $w->delete();
        $this->logger->log('حذف مخزن', $w->name);
        return response()->json(['ok' => true]);
    }
}
