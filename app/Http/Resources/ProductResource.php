<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Product */
class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'buyPrice' => $this->buy_price,
            'sellPrice' => $this->sell_price,
            'quantity' => $this->quantity,
            'warehouseId' => $this->warehouse_id,
            'stockValue' => $this->quantity * $this->sell_price,
            'createdAt' => $this->created_at?->toIso8601String(),
        ];
    }
}
