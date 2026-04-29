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

        foreach ($this->resource->roles ?? [] as $role) {
            $allPermissions = $allPermissions->merge($role->permissions ?? []);
        }

        $allPermissions = $allPermissions->merge($this->resource->permissions ?? []);

        $finalPermissions = $allPermissions->pluck('name')->unique()->values();
        
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
