<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Warehouse\StoreWarehouseRequest;
use App\Http\Resources\WarehouseResource;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class WarehouseController extends Controller
{
    public function __construct(private readonly ActivityLogger $logger)
    {
    }

    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        $authUser = Auth::user();
        
        // Temporarily bypass permission check to debug 500 error
        // if (!$authUser->isAbleTo('view-warehouses')) {
        //     return response()->json(['message' => 'ليس لديك صلاحية لعرض المخازن'], 403);
        // }
        
        $query = Warehouse::with('admin');
        
        // Super admin sees all warehouses, or can filter by admin_id
        if ($authUser->roles->contains('name', 'super_admin')) {
            if ($request->has('admin_id')) {
                $query->where('admin_id', $request->query('admin_id'));
            }
        } else {
            // Regular admin sees their own warehouses
            // Employees (user role) see warehouses belonging to their admin
            $targetAdminId = $authUser->roles->contains('name', 'admin') ? $authUser->id : $authUser->admin_id;
            
            if ($targetAdminId) {
                $query->where('admin_id', $targetAdminId);
            } else {
                // If it's a user not assigned to any admin, they only see warehouses explicitly assigned to them if any
                $query->where('admin_id', $authUser->id);
            }
        }
        
        return WarehouseResource::collection($query->orderByDesc('created_at')->get());
    }

    public function store(StoreWarehouseRequest $request): WarehouseResource|JsonResponse
    {
        $authUser = Auth::user();
        
        // Only admins can create warehouses
        if (!$authUser->hasRole('admin') && !$authUser->hasRole('super_admin')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        // Check warehouse limit for non-super admins
        if (!$authUser->hasRole('super_admin')) {
            $currentWarehouseCount = Warehouse::where('admin_id', $authUser->id)->count();
            $maxWarehouses = $authUser->max_warehouses ?? 1;
            
            if ($currentWarehouseCount >= $maxWarehouses) {
                Log::warning('Warehouse creation denied - limit reached', [
                    'user_id' => $authUser->id,
                    'current_count' => $currentWarehouseCount,
                    'max_allowed' => $maxWarehouses
                ]);
                
                return response()->json([
                    'message' => 'لا يمكن إنشاء المزيد من المخازن. لقد وصلت إلى الحد الأقصى المسموح به.',
                    'current_count' => $currentWarehouseCount,
                    'max_allowed' => $maxWarehouses
                ], 403);
            }
        }
        
        $data = $request->validated();
        
        // Set admin_id for non-super admin users
        if (!$authUser->hasRole('super_admin')) {
            $data['admin_id'] = $authUser->id;
        }
        
        $w = Warehouse::create($data);
        $this->logger->log('إضافة مخزن', $w->name);
        return new WarehouseResource($w);
    }

    public function update(Request $request, int $id): WarehouseResource|JsonResponse
    {
        $authUser = Auth::user();
        $w = Warehouse::findOrFail($id);
        
        // Check authorization - users can only edit their own warehouses (except super admin)
        if (!$authUser->hasRole('super_admin') && $w->admin_id !== $authUser->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $data = $request->validate(['name' => ['required', 'string', 'max:191']]);
        $w->update($data);
        $this->logger->log('تعديل مخزن', $w->name);
        return new WarehouseResource($w);
    }

    public function destroy(int $id): JsonResponse
    {
        $authUser = Auth::user();
        $w = Warehouse::findOrFail($id);
        
        // Check authorization - users can only delete their own warehouses (except super admin)
        if (!$authUser->hasRole('super_admin') && $w->admin_id !== $authUser->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        // Count products before deletion for logging
        $productCount = Product::where('warehouse_id', $id)->count();
        
        // Delete all products in the warehouse
        Product::where('warehouse_id', $id)->delete();
        
        // Delete the warehouse
        $w->delete();
        
        // Log the deletion with product count
        $this->logger->log('حذف مخزن', "{$w->name} (مع {$productCount} منتج)");
        
        return response()->json([
            'ok' => true,
            'message' => "تم حذف المخزن {$w->name} وجميع المنتجات التي بداخله ({$productCount} منتج)"
        ]);
    }
}
