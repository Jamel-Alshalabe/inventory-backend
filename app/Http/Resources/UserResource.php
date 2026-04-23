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
        return [
            'id' => $this->id,
            'username' => $this->username,
            'role' => $this->role->value,
            'assignedWarehouseId' => $this->assigned_warehouse_id,
            'createdAt' => $this->created_at?->toIso8601String(),
        ];
    }
}
