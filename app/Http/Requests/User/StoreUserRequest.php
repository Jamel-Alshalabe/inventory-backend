<?php

declare(strict_types=1);

namespace App\Http\Requests\User;

use App\Enums\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    public function rules(): array
    {
        return [
            'username' => ['required', 'string', 'min:3', 'max:64', 'unique:users,username'],
            'password' => ['required', 'string', 'min:6', 'max:128'],
            'role' => ['required', new Enum(Role::class)],
            'assignedWarehouseId' => ['nullable', 'integer', 'exists:warehouses,id'],
        ];
    }
}
