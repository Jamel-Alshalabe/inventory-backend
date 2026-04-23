<?php

declare(strict_types=1);

namespace App\Http\Requests\Movement;

use App\Enums\MovementType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->canMutate();
    }

    public function rules(): array
    {
        return [
            'type' => ['required', new Enum(MovementType::class)],
            'productCode' => ['required', 'string', 'max:64'],
            'quantity' => ['required', 'integer', 'min:1'],
            'price' => ['required', 'numeric', 'min:0'],
            'warehouseId' => ['nullable', 'integer', 'exists:warehouses,id'],
        ];
    }
}
