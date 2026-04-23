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
        $this->middleware('permission:manage-users');
    }

    public function index(): AnonymousResourceCollection
    {
        $authUser = Auth::user();
        
        $query = User::with(['admin', 'assignedWarehouse', 'roles', 'permissions']);
        
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
            'role' => $data['role'],
            'assigned_warehouse_id' => $data['assignedWarehouseId'] ?? null,
            'company_name' => $data['company_name'] ?? null,
            'company_phone' => $data['company_phone'] ?? null,
            'company_address' => $data['company_address'] ?? null,
            'company_currency' => $data['company_currency'] ?? null,
           
        ]);
        
        // Assign role
        if (isset($data['role'])) {
            $role = Role::where('name', $data['role'])->first();
            if ($role) {
                $user->attachRole($role);
            }
        }
        
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
        
        // Check if user can update this user
        if (!$authUser->hasRole('super_admin') && $user->admin_id !== $authUser->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $data = $request->validated();
        $patch = array_filter([
            'username' => $data['username'] ?? null,
            'role' => $data['role'] ?? null,
            'assigned_warehouse_id' => array_key_exists('assignedWarehouseId', $data)
                ? $data['assignedWarehouseId']
                : null,
            'company_name' => $data['company_name'] ?? null,
            'company_phone' => $data['company_phone'] ?? null,
            'company_address' => $data['company_address'] ?? null,
            'company_currency' => $data['company_currency'] ?? null,
            
        ], static fn ($v) => $v !== null);

        // Don't allow changing admin_id unless super admin
        if (!$authUser->hasRole('super_admin')) {
            unset($patch['admin_id']);
        }

        if (! empty($data['password'])) {
            $patch['password'] = Hash::make($data['password']);
        }
        
        $user->fill($patch)->save();
        
        // Update role
        if (isset($data['role'])) {
            $role = Role::where('name', $data['role'])->first();
            if ($role) {
                $user->syncRoles([$role]);
            }
        }
        
        // Update permissions
        if (isset($data['permissions']) && is_array($data['permissions'])) {
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
        if (!$authUser->hasRole('super_admin') && $user->admin_id !== $authUser->id) {
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
