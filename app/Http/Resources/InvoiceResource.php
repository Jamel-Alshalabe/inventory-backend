<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Invoice */
class InvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'invoiceNumber' => $this->invoice_number,
            'customerName' => $this->customer_name,
            'items' => $this->items,
            'total' => $this->total,
            'status' => $this->status,
            'warehouseId' => $this->warehouse_id,
            'createdAt' => $this->created_at?->toIso8601String(),
        ];
    }
}
