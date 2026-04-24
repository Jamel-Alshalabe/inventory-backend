<?php

declare(strict_types=1);

namespace App\Http\Requests\Account;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    public function rules(): array
    {
        return [
            'username' => ['sometimes', 'string', 'max:255', 'unique:users,username,' . $this->user()->id],
            'currentPassword' => ['required_with:username,newPassword', 'string'],
            'newPassword' => ['sometimes', 'string', 'min:8', 'confirmed'],
            'company_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'company_phone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'company_address' => ['sometimes', 'nullable', 'string', 'max:500'],
            'company_currency' => ['sometimes', 'nullable', 'string', 'max:10'],
        ];
    }
}
