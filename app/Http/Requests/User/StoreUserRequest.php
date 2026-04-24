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
        $user = $this->user();
        
        // Debug: Log user information
        if ($user) {
            \Log::info('StoreUserRequest Debug:', [
                'user_id' => $user->id,
                'username' => $user->username,
                'isAdmin' => $user->isAdmin(),
                'hasRole_super_admin' => $user->hasRole('super_admin'),
                'allRoles' => $user->roles->pluck('name')->toArray(),
            ]);
        }
        
        return $user && ($user->isAdmin() || $user->hasRole('super_admin'));
    }

    public function rules(): array
    {
        return [
            'username' => ['required', 'string', 'min:3', 'max:64', 'unique:users,username'],
            'password' => ['required', 'string', 'min:6', 'max:128'],
            'role' => ['required', new Enum(Role::class)],
            'assignedWarehouseId' => ['nullable', 'integer', 'exists:warehouses,id'],
            'max_warehouses' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
