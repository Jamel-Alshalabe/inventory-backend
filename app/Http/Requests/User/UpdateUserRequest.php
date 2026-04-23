<?php

declare(strict_types=1);

namespace App\Http\Requests\User;

use App\Enums\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    public function rules(): array
    {
        $id = (int) $this->route('user');
        return [
            'username' => ['sometimes', 'string', 'min:3', 'max:64', Rule::unique('users', 'username')->ignore($id)],
            'password' => ['sometimes', 'nullable', 'string', 'min:6', 'max:128'],
            'role' => ['sometimes', new Enum(Role::class)],
            'assignedWarehouseId' => ['sometimes', 'nullable', 'integer', 'exists:warehouses,id'],
        ];
    }
}
