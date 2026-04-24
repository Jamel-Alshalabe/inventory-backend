<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function __construct(private readonly ActivityLogger $logger)
    {
    }

    public function index(): AnonymousResourceCollection
    {
        $authUser = Auth::user();
        
        $query = User::with(['admin', 'assignedWarehouse', 'roles.permissions', 'permissions'])
        ->whereHas('roles', function($query) {
            $query->where('name', '!=', 'super_admin');
        });
        
        
        // If not super admin, only show employees of the current admin
        if (!$authUser->hasRole('super_admin')) {
            $query->where('admin_id', $authUser->id);
           
        } 
        
        $users = $query->orderBy('username')->get();
        
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
            'username' => $data['username'],
            'password' => $data['password'],
            'assigned_warehouse_id' => $data['assignedWarehouseId'] ?? null,
            'max_warehouses' => $data['max_warehouses'] ?? 1,
            'company_name' => $data['company_name'] ?? null,
            'company_phone' => $data['company_phone'] ?? null,
            'company_address' => $data['company_address'] ?? null,
            'company_currency' => $data['company_currency'] ?? 'ج.م',
        ]);
        
        // Assign role using Laratrust
        $user->addRole($data['role']);
        
        // Assign specific permissions
        if (isset($data['permissions']) && is_array($data['permissions'])) {
            $permissions = Permission::whereIn('name', $data['permissions'])->get();
            $user->syncPermissions($permissions);
        }
        
        $this->logger->log('إضافة مستخدم', $user->username);
        return new UserResource($user->load(['admin', 'roles', 'permissions']));
    }

    public function update(UpdateUserRequest $request, int $id): UserResource
    {
        $authUser = Auth::user();
        $user = User::findOrFail($id);
        
        // Debug logging
        \Log::info('User update authorization check', [
            'auth_user_id' => $authUser->id,
            'auth_user_roles' => $authUser->roles->pluck('name')->toArray(),
            'target_user_id' => $user->id,
            'target_user_admin_id' => $user->admin_id,
            'has_super_admin_role' => $authUser->hasRole('super_admin'),
            'has_admin_role' => $authUser->hasRole('admin'),
            'has_any_admin_role' => $authUser->hasRole(['super_admin', 'admin'])
        ]);
        
        // Check if user can update this user
        if (!$authUser->hasRole(['super_admin', 'admin']) && $user->admin_id !== $authUser->id) {
            \Log::error('User update authorization failed', [
                'reason' => 'User does not have required role and is not the admin of target user'
            ]);
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
                } elseif ($data[$field] !== null) {
                    $patch[$field] = $data[$field];
                }
            }
        }
        
        // Note: Role is handled separately via relationship, not in patch

        // Don't allow changing admin_id unless super admin
        if (!$authUser->hasRole('super_admin')) {
            unset($patch['admin_id']);
        }
        
        $user->fill($patch)->save();
        
        \Log::info('User updated successfully', [
            'user_id' => $id,
            'patch_data' => $patch,
            'updated_fields' => array_keys($patch)
        ]);
        
        // Update role (only super admin can change roles)
        if ($authUser->hasRole('super_admin') && isset($data['role'])) {
            $role = Role::where('name', $data['role'])->first();
            if ($role) {
                $user->syncRoles([$role]);
            }
        }
        
        // Update permissions (only super admin can change permissions)
        if ($authUser->hasRole('super_admin') && isset($data['permissions']) && is_array($data['permissions'])) {
            $permissions = Permission::whereIn('name', $data['permissions'])->get();
            $user->syncPermissions($permissions);
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
            // Regular admins can't create other admins
            $roles = $roles->filter(function ($role) {
                return !in_array($role->name, ['super_admin', 'admin']);
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
        
        return response()->json(['permissions' => $permissions]);
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
        
        return response()->json([
            'message' => 'Permissions updated successfully',
            'user' => $user->load(['roles', 'permissions']),
        ]);
    }
}
