<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Warehouse */
class WarehouseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'admin_id' => $this->admin_id,
            'admin' => $this->whenLoaded('admin', fn() => [
                'id' => $this->admin->id,
                'username' => $this->admin->username,
            ]),
            'createdAt' => $this->created_at?->toIso8601String(),
        ];
    }
}
