<?php

declare(strict_types=1);

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class BulkProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->canMutate();
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.name' => ['required', 'string', 'max:191'],
            'items.*.code' => ['required', 'string', 'max:64'],
            'items.*.buyPrice' => ['required', 'numeric', 'min:0'],
            'items.*.sellPrice' => ['required', 'numeric', 'min:0'],
            'items.*.quantity' => ['required', 'integer', 'min:0'],
            'warehouseId' => ['nullable', 'integer', 'exists:warehouses,id'],
        ];
    }
}
