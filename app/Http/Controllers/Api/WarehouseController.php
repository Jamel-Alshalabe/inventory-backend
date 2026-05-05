<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Warehouse\StoreWarehouseRequest;
use App\Http\Resources\WarehouseResource;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\User;
use App\Models\Movement;
use App\Models\Invoice;
use App\Exports\WarehouseDataExport;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class WarehouseController extends Controller
{
    public function __construct(private readonly ActivityLogger $logger)
    {
    }

    public function exportWarehouseData(int $id, Request $request): SymfonyResponse|JsonResponse
    {
        

        $authUser = Auth::user();
        
        // 1. Check if user has the specific export permission
        try {
            $allowed = $authUser && ($authUser->hasRole('admin') || $authUser->isAbleTo('export-warehouse'));
        } catch (\Throwable $e) {
            Log::error('Export permission check crashed', [
                'error' => $e->getMessage(),
                'type' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return response()->json([
                'message' => 'حدث خطأ أثناء التحقق من صلاحيات التصدير',
                'error' => $e->getMessage(),
            ], 500);
        }

        if (!$allowed) {
            return response()->json(['message' => 'ليس لديك صلاحية لتصدير بيانات المخزن'], 403);
        }

        try {
            $warehouse = Warehouse::with(['admin'])->findOrFail($id);

            // 2. Check authorization to access THIS specific warehouse
            if (!$authUser->hasRole('admin') && $warehouse->admin_id !== $authUser->id && $authUser->admin_id !== $warehouse->admin_id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $type = $request->query('type', 'products');
            $fileName = "مخزن_{$warehouse->name}_{$type}_" . now()->format('Y-m-d_H-i-s') . ".xlsx";

            switch ($type) {
                case 'products':
                    $data = $warehouse->products()->get()->map(function ($p) {
                        return [
                            'ID' => $p->id,
                            'Code' => $p->code,
                            'Name' => $p->name,
                            'Quantity' => $p->quantity,
                            'Low Stock Threshold' => $p->low_stock_threshold,
                            'Buy Price' => $p->buy_price,
                            'Sell Price' => $p->sell_price,
                            'Created At' => $p->created_at,
                        ];
                    });
                    $headings = ['رقم المعرف', 'الكود', 'الاسم', 'الكمية', 'حد المخزون المنخفض', 'سعر الشراء', 'سعر البيع', 'تاريخ الإضافة'];
                    $title = 'المنتجات';
                    break;

                case 'movements':
                    $data = $warehouse->movements()->get()->map(function ($m) {
                        return [
                            'ID' => $m->id,
                            'Type' => $m->type->value === 'in' ? 'وارد' : 'صادر',
                            'Product Code' => $m->product_code,
                            'Product Name' => $m->product_name,
                            'Quantity' => $m->quantity,
                            'Price' => $m->price,
                            'Total' => $m->total,
                            'Date' => $m->created_at,
                        ];
                    });
                    $headings = ['رقم المعرف', 'النوع', 'كود المنتج', 'اسم المنتج', 'الكمية', 'السعر', 'الإجمالي', 'التاريخ'];
                    $title = 'حركة المخزون';
                    break;

                case 'invoices':
                    $data = $warehouse->invoices()->get()->map(function ($i) {
                        return [
                            'ID' => $i->id,
                            'Invoice Number' => $i->invoice_number,
                            'items_count' => $i->items()->count(),
                            'Customer Name' => $i->customer_name,
                            'Total' => $i->total,
                            'Status' => $i->status,
                            'Date' => $i->created_at,
                        ];
                    });
                    $headings = ['رقم المعرف', 'رقم الفاتورة', 'عدد الأصناف', 'اسم العميل', 'الإجمالي', 'الحالة', 'التاريخ'];
                    $title = 'الفواتير';
                    break;

                case 'users':
                    $adminId = $authUser->hasRole('admin') ? $authUser->id : $authUser->admin_id;

                    $data = User::with(['roles'])
                        ->where(function ($query) use ($id, $adminId, $warehouse) {
                            $query
                                ->where(function ($q) use ($id, $adminId) {
                                    $q->where('assigned_warehouse_id', $id);

                                    if ($adminId) {
                                        $q->where('admin_id', $adminId);
                                    }
                                })
                                ->orWhere('id', $warehouse->admin_id);
                        })
                        ->get()
                        ->map(function ($u) {
                            return [
                                'ID' => $u->id,
                                'Username' => $u->username,
                                'Email' => $u->email ? $u->email : 'فارغ',
                                'Role' => $u->roles->first()?->display_name ?? $u->role,
                                'Created At' => $u->created_at,
                            ];
                        });
                    $headings = ['رقم المعرف', 'اسم المستخدم', 'البريد الإلكتروني', 'الدور', 'تاريخ الإنشاء'];
                    $title = 'المستخدمين';
                    break;

                default:
                    return response()->json(['message' => 'Invalid export type'], 400);
            }

            $this->logger->log('تصدير بيانات مخزن', "تصدير {$title} لمخزن {$warehouse->name}");

            $export = new WarehouseDataExport($data, $headings, $title);
            return Excel::download($export, $fileName);

        } catch (\Throwable $e) {
            Log::error('CRITICAL: Export failed', [
                'error' => $e->getMessage(),
                'type' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'message' => 'حدث خطأ داخلي أثناء تصدير الملف',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        $authUser = Auth::user();
        
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
