<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Movement */
class MovementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'productCode' => $this->product_code,
            'productName' => $this->product_name,
            'quantity' => $this->quantity,
            'price' => $this->price,
            'total' => $this->total,
            'warehouseId' => $this->warehouse_id,
            'createdAt' => $this->created_at?->toIso8601String(),
        ];
    }
}
