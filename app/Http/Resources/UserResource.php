<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\User */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Get all permissions from user's roles and direct permissions
        $allPermissions = collect();
        
        // Debug: Log roles and permissions
        \Log::info('UserResource Debug for user ' . $this->id, [
            'roles_count' => $this->resource->roles->count(),
            'roles' => $this->resource->roles->pluck('name')->toArray(),
            'permissions_relation_loaded' => $this->resource->relationLoaded('permissions'),
            'direct_permissions_count' => $this->resource->relationLoaded('permissions') ? $this->resource->permissions->count() : 'not loaded'
        ]);
        
        // Add permissions from roles
        foreach ($this->resource->roles as $role) {
            \Log::info('Processing role: ' . $role->name, [
                'permissions_loaded' => $role->relationLoaded('permissions'),
                'permissions_count' => $role->relationLoaded('permissions') ? $role->permissions->count() : 'not loaded'
            ]);
            
            // Force load permissions if not already loaded
            if (!$role->relationLoaded('permissions')) {
                $role->load('permissions');
                \Log::info('Force loaded permissions for role: ' . $role->name, [
                    'permissions_count' => $role->permissions->count(),
                    'permissions' => $role->permissions->pluck('name')->toArray()
                ]);
            }
            
            $allPermissions = $allPermissions->merge($role->permissions);
        }
        
        // Add direct user permissions
        if (!$this->resource->relationLoaded('permissions')) {
            $this->resource->load('permissions');
            \Log::info('Force loaded direct permissions for user: ' . $this->id, [
                'permissions_count' => $this->resource->permissions->count(),
                'permissions' => $this->resource->permissions->pluck('name')->toArray()
            ]);
        }
        
        $allPermissions = $allPermissions->merge($this->resource->permissions);
        
        $finalPermissions = $allPermissions->pluck('name')->unique()->values();
        
        \Log::info('Final permissions for user ' . $this->id, [
            'permissions' => $finalPermissions->toArray(),
            'count' => $finalPermissions->count()
        ]);
        
        return [
            'id' => $this->id,
            'username' => $this->username,
            'role' => $this->roles->first()?->name ?? null, // Use Laratrust roles
            'permissions' => $finalPermissions, // Get all unique permissions
            'assignedWarehouseId' => $this->assigned_warehouse_id,
            'assignedWarehouseName' => $this->assignedWarehouse?->name,
            'max_warehouses' => $this->max_warehouses,
            'createdAt' => $this->created_at?->toIso8601String(),
        ];
    }
}
