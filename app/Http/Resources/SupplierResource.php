<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Supplier */
class SupplierResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'firstName' => $this->first_name,
            'lastName' => $this->last_name,
            'email' => $this->email,
            'phoneNumber' => $this->phone_number,
            'whatsappNumber' => $this->whatsapp_number,
            'address' => $this->address,
            'companyName' => $this->company_name,
            'companyAddress' => $this->company_address,
            'createdAt' => $this->created_at?->toIso8601String(),
        ];
    }
}
