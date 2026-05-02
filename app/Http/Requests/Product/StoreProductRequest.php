<?php

declare(strict_types=1);

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->canMutate();
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:191'],
            'code' => ['required', 'string', 'max:64'],
            'buyPrice' => ['required', 'numeric', 'min:0'],
            'sellPrice' => ['required', 'numeric', 'min:0'],
            'quantity' => ['required', 'integer', 'min:0'],
            'lowStockThreshold' => ['nullable', 'integer', 'min:0'],
            'warehouseId' => ['nullable', 'integer', 'exists:warehouses,id'],
        ];
    }
}
