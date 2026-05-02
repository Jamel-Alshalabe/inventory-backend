<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Models\Role;
use App\Models\Warehouse;
use App\Models\Permission;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function __construct(private readonly ActivityLogger $logger)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $authUser = Auth::user();
        
        $searchTerm = $request->query('q');
        
        $query = User::with(['admin', 'creator', 'assignedWarehouse', 'roles.permissions', 'permissions'])
        ->search($searchTerm)
        ->whereHas('roles', function($query) {
            $query->where('name', '!=', 'super_admin');
        });
        
        // If super admin, only show users they created
        if ($authUser->hasRole('super_admin')) {
            $query->where('created_by_id', $authUser->id);
        } 
        // If regular admin, only show their employees
        elseif ($authUser->hasRole('admin')) {
            $query->where('admin_id', $authUser->id);
        } else {
            // Other roles shouldn't see anyone or only themselves, but here we enforce admin/superadmin logic
            $query->where('id', $authUser->id);
        }
        
        $users = $query->orderByDesc('created_at')->get();
        
        return UserResource::collection($users);
    }

    public function store(StoreUserRequest $request): UserResource
    {
        $admin = Auth::user();
        
        // Only admins can create users
        if (!$admin->hasRole('admin') && !$admin->hasRole('super_admin')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $data = $request->validated();
        
        // Set admin_id for non-super admin users
        if (!$admin->hasRole('super_admin')) {
            $data['admin_id'] = $admin->id;
        }
        
        // Hash password
        $data['password'] = Hash::make($data['password']);
        
        $user = User::create([
            'admin_id' => $data['admin_id'] ?? null,
            'created_by_id' => $admin->id,
            'username' => $data['username'],
            'password' => $data['password'],
            'assigned_warehouse_id' => $data['assignedWarehouseId'] ?? null,
            'max_warehouses' => $admin->hasRole('super_admin') ? ($data['max_warehouses'] ?? 1) : 1,
        ]);
        
        $roleName = $data['role'];
        $role = Role::where('name', $roleName)->first();
        
        if ($role) {
            $user->syncRoles([$role]);
        }
        
        // Task 1: If the created user is an 'admin' (manager), create a main warehouse for them
        if ($roleName === 'admin') {
            Warehouse::create([
                'name' => 'المخزن الرئيسي',
                'admin_id' => $user->id,
            ]);
        }
        
        // Assign specific permissions
        if (isset($data['permissions']) && is_array($data['permissions'])) {
            $permissions = Permission::whereIn('name', $data['permissions'])->get();
            $user->syncPermissions($permissions->all()); // Pass array instead of collection
        }
        
        $user->load(['roles', 'permissions']); // Ensure permissions are loaded
        
        $this->logger->log('إضافة مستخدم', $user->username);
        return new UserResource($user->load(['admin', 'roles', 'permissions']));
    }

    public function update(UpdateUserRequest $request, int $id): UserResource
    {
        $authUser = Auth::user();
        $user = User::findOrFail($id);
        
        // Check if user can update this user
        if (!$authUser->hasRole(['super_admin', 'admin']) && $user->admin_id !== $authUser->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $data = $request->validated();
        $editableFields = $user->getEditableFields();
        
        // Filter data to only include editable fields for this user role
        $patch = [];
        
        foreach ($editableFields as $field) {
            if (array_key_exists($field, $data)) {
                if ($field === 'assignedWarehouseId') {
                    $patch['assigned_warehouse_id'] = $data[$field] !== null ? $data[$field] : null;
                } elseif ($field === 'maxWarehouses') {
                    $patch['max_warehouses'] = $data[$field] !== null ? $data[$field] : 1;
                } elseif ($field === 'password' && !empty($data[$field])) {
                    $patch[$field] = Hash::make($data[$field]);
                } elseif ($field === 'phone2') {
                    $patch['phone2'] = $data[$field];
                } elseif ($data[$field] !== null) {
                    $patch[$field] = $data[$field];
                }
            }
        }
        
        // Don't allow changing admin_id unless super admin
        if (!$authUser->hasRole('super_admin')) {
            unset($patch['admin_id']);
        }
        
        $user->fill($patch)->save();
        
        // Update role (super admin can change all, admin can only change their employees' roles)
        if (isset($data['role'])) {
            $role = Role::where('name', $data['role'])->first();
            if ($role) {
                // Security check for regular admin
                if ($authUser->hasRole('super_admin') || !in_array($role->name, ['super_admin', 'admin'])) {
                    $user->syncRoles([$role]);
                }
            }
        }
        
        // Update permissions
        if (isset($data['permissions']) && is_array($data['permissions'])) {
            $permissions = Permission::whereIn('name', $data['permissions'])->get();
            $user->syncPermissions($permissions->all()); // Pass array instead of collection
        }
        
        $this->logger->log('تعديل مستخدم', $user->username);
        return new UserResource($user->load(['admin', 'roles', 'permissions']));
    }

    public function destroy(int $id): JsonResponse
    {
        $authUser = Auth::user();
        $user = User::findOrFail($id);
        
        // Check if user can delete this user
        if (!$authUser->hasRole(['super_admin', 'admin']) && $user->admin_id !== $authUser->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        // Prevent admin from deleting themselves
        if ($user->id === $authUser->id) {
            return response()->json(['message' => 'Cannot delete yourself'], 400);
        }
        
        $user->delete();
        $this->logger->log('حذف مستخدم', $user->username);
        return response()->json(['ok' => true]);
    }

    /**
     * Get available roles for creating users
     */
    public function getRoles(): JsonResponse
    {
        $authUser = Auth::user();
        
        $roles = Role::all();
        
        // Filter roles based on auth user
        if (!$authUser->hasRole('super_admin')) {
            // Regular admins can create other admins (managers)
            $roles = $roles->filter(function ($role) {
                return $role->name !== 'super_admin';
            });
        }
        
        return response()->json(['roles' => $roles]);
    }

    /**
     * Get available permissions
     */
    public function getPermissions(): JsonResponse
    {
        $permissions = Permission::all();
        
        // Group permissions by category
        $grouped = [];
        foreach ($permissions as $permission) {
            $name = $permission->name;
            $category = 'أخرى';
            
            if (str_contains($name, 'user')) $category = 'المستخدمين';
            elseif (str_contains($name, 'product')) $category = 'المنتجات';
            elseif (str_contains($name, 'warehouse')) $category = 'المخازن';
            elseif (str_contains($name, 'invoice')) $category = 'الفواتير';
            elseif (str_contains($name, 'movement')) $category = 'حركات المخزون';
            elseif (str_contains($name, 'subscription')) $category = 'الاشتراكات';
            elseif (str_contains($name, 'report')) $category = 'التقارير';
            elseif (str_contains($name, 'setting')) $category = 'الإعدادات';
            elseif (str_contains($name, 'log')) $category = 'السجلات';
            elseif (str_contains($name, 'dashboard')) $category = 'لوحة التحكم';
            
            $grouped[$category][] = $permission;
        }
        
        return response()->json([
            'permissions' => $permissions,
            'grouped' => $grouped
        ]);
    }

    /**
     * Update user permissions
     */
    public function updatePermissions(Request $request, User $user): JsonResponse
    {
        $authUser = Auth::user();
        
        // Check if user can update this user's permissions
        if (!$authUser->hasRole('super_admin') && $user->admin_id !== $authUser->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);
        
        $permissions = Permission::whereIn('name', $request->permissions)->get();
        $user->syncPermissions($permissions);
        
        // Log the activity
        $this->logger->log('تحديث صلاحيات', "تحديث صلاحيات المستخدم {$user->username}");
        
        return response()->json([
            'message' => 'Permissions updated successfully',
            'user' => $user->load(['roles', 'permissions']),
        ]);
    }
}
