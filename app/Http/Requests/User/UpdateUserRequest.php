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
        $user = $this->user();
        if (!$user) {
            return false;
        }
        
        // Allow super admin and admin
        return $user->hasRole(['super_admin', 'admin']);
    }

    public function rules(): array
    {
        $id = (int) $this->route('user');
        return [
            'username' => ['sometimes', 'string', 'min:3', 'max:64', Rule::unique('users', 'username')->ignore($id)],
            'password' => ['sometimes', 'nullable', 'string', 'min:6', 'max:128'],
            'role' => ['sometimes', new Enum(Role::class)],
            'assignedWarehouseId' => ['sometimes', 'nullable', 'integer', 'exists:warehouses,id'],
            'maxWarehouses' => ['sometimes', 'integer', 'min:1', 'max:999'],
            'company_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'company_phone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'company_address' => ['sometimes', 'nullable', 'string', 'max:500'],
            'company_currency' => ['sometimes', 'nullable', 'string', 'max:10'],
        ];
    }
}
