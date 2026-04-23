<?php

declare(strict_types=1);

namespace App\Http\Requests\Invoice;

use Illuminate\Foundation\Http\FormRequest;

class StoreInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->canMutate();
    }

    public function rules(): array
    {
        return [
            'customerName' => ['required', 'string', 'max:191'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.productCode' => ['required', 'string', 'max:64'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.price' => ['nullable', 'numeric', 'min:0'],
            'warehouseId' => ['nullable', 'integer', 'exists:warehouses,id'],
        ];
    }
}
