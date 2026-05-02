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
        // Get all permissions (both direct and through roles)
        $allPermissions = $this->resource->allPermissions()->pluck('name')->unique()->values()->toArray();
        
        return [
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
            'role' => $this->roles->first()?->name ?? null, 
            'permissions' => $allPermissions,
            'assignedWarehouseId' => $this->assigned_warehouse_id,
            'assignedWarehouseName' => $this->assignedWarehouse?->name,
            'maxWarehouses' => $this->max_warehouses,
            'createdById' => $this->created_by_id,
            'createdAt' => $this->created_at?->toIso8601String(),
            'companyName' => $this->company_name,
            'companyPhone' => $this->company_phone,
            'phone2' => $this->phone2,
            'companyAddress' => $this->company_address,
            'companyCurrency' => $this->company_currency,
        ];
    }
}
