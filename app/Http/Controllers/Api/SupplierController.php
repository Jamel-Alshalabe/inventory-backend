<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supplier\StoreSupplierRequest;
use App\Http\Requests\Supplier\UpdateSupplierRequest;
use App\Http\Resources\SupplierResource;
use App\Models\Supplier;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SupplierController extends Controller
{
    public function __construct(private readonly ActivityLogger $logger)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        /** @var User $authUser */
        $authUser = $request->user();
        $authUser->loadMissing(['roles', 'permissions']);

        $q = Supplier::query();

        if (!$authUser->hasRole('super_admin')) {
            $q->where('admin_id', $authUser->id);
        }

        $search = trim((string) $request->query('q', ''));
        if ($search !== '') {
            $q->where(function ($w) use ($search): void {
                $w->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone_number', 'like', "%{$search}%")
                    ->orWhere('whatsapp_number', 'like', "%{$search}%");
            });
        }

        $rows = $q->orderByDesc('created_at')->get();
        return SupplierResource::collection($rows);
    }

    public function show(Request $request, int $id): SupplierResource
    {
        /** @var User $authUser */
        $authUser = $request->user();

        $q = Supplier::query()->where('id', $id);
        if (!$authUser->hasRole('super_admin')) {
            $q->where('admin_id', $authUser->id);
        }

        return new SupplierResource($q->firstOrFail());
    }

    public function store(StoreSupplierRequest $request): SupplierResource
    {
        /** @var User $authUser */
        $authUser = $request->user();

        $data = $request->validated();

        $supplier = Supplier::create([
            'admin_id' => $authUser->hasRole('super_admin') ? ($authUser->id) : $authUser->id,
            'first_name' => $data['firstName'],
            'last_name' => $data['lastName'] ?? null,
            'email' => $data['email'] ?? null,
            'phone_number' => $data['phoneNumber'] ?? null,
            'whatsapp_number' => $data['whatsappNumber'] ?? null,
            'address' => $data['address'] ?? null,
            'company_name' => $data['companyName'] ?? null,
            'company_address' => $data['companyAddress'] ?? null,
        ]);

        $this->logger->log('إضافة مورد', $supplier->first_name);
        return new SupplierResource($supplier);
    }

    public function update(UpdateSupplierRequest $request, int $id): SupplierResource
    {
        /** @var User $authUser */
        $authUser = $request->user();

        $q = Supplier::query()->where('id', $id);
        if (!$authUser->hasRole('super_admin')) {
            $q->where('admin_id', $authUser->id);
        }
        $supplier = $q->firstOrFail();

        $data = $request->validated();

        $payload = collect($data)
            ->mapWithKeys(fn ($v, string $k) => [match ($k) {
                'firstName' => 'first_name',
                'lastName' => 'last_name',
                'phoneNumber' => 'phone_number',
                'whatsappNumber' => 'whatsapp_number',
                'companyName' => 'company_name',
                'companyAddress' => 'company_address',
                default => $k,
            } => $v])
            ->all();

        $supplier->fill($payload)->save();

        $this->logger->log('تعديل مورد', $supplier->first_name);
        return new SupplierResource($supplier);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        /** @var User $authUser */
        $authUser = $request->user();
        $authUser->loadMissing(['roles', 'permissions']);

        try {
            $allowed = $authUser->hasRole('super_admin') || $authUser->isAbleTo('delete-supplier');
        } catch (\Throwable) {
            $allowed = false;
        }

        if (!$allowed) {
            return response()->json(['message' => 'ليس لديك صلاحية لحذف الموردين'], 403);
        }

        $q = Supplier::query()->where('id', $id);
        if (!$authUser->hasRole('super_admin')) {
            $q->where('admin_id', $authUser->id);
        }
        $supplier = $q->firstOrFail();

        $supplier->delete();
        $this->logger->log('حذف مورد', $supplier->first_name);

        return response()->json(['ok' => true]);
    }
}
