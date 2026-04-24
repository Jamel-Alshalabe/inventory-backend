<?php

declare(strict_types=1);

namespace App\Http\Requests\Account;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUsernameRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'username' => [
                'required', 'string', 'min:3', 'max:64',
                Rule::unique('users', 'username')->ignore($this->user()->id),
            ],
        ];
    }
}
