<?php

declare(strict_types=1);

namespace App\Http\Requests\Supplier;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        $u = $this->user();
        if (!$u) return false;

        $u->loadMissing(['roles', 'permissions']);

        try {
            return $u->hasRole('admin') || $u->isAbleTo('edit-supplier');
        } catch (\Throwable) {
            return false;
        }
    }

    public function rules(): array
    {
        return [
            'firstName' => ['sometimes', 'string', 'max:191'],
            'lastName' => ['sometimes', 'nullable', 'string', 'max:191'],
            'email' => ['sometimes', 'nullable', 'email', 'max:191'],
            'phoneNumber' => ['sometimes', 'nullable', 'string', 'max:64'],
            'whatsappNumber' => ['sometimes', 'nullable', 'string', 'max:64'],
            'address' => ['sometimes', 'nullable', 'string', 'max:191'],
            'companyName' => ['sometimes', 'nullable', 'string', 'max:191'],
            'companyAddress' => ['sometimes', 'nullable', 'string', 'max:191'],
        ];
    }
}
