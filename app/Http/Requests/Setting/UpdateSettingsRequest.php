<?php

declare(strict_types=1);

namespace App\Http\Requests\Setting;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    public function rules(): array
    {
        return [
            'companyName' => ['sometimes', 'string', 'max:191'],
            'companyPhone' => ['sometimes', 'string', 'max:64'],
            'companyPhone2' => ['sometimes', 'string', 'max:64', 'nullable'],
            'companyEmail' => ['sometimes', 'email', 'max:191', 'nullable'],
            'companyAddress' => ['sometimes', 'string', 'max:255'],
            'currency' => ['sometimes', 'string', 'max:16'],
        ];
    }
}
