<?php

declare(strict_types=1);

namespace App\Http\Requests\Supplier;

use Illuminate\Foundation\Http\FormRequest;

class StoreSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        $u = $this->user();
        if (!$u) return false;

        $u->loadMissing(['roles', 'permissions']);

        try {
            return $u->hasRole('admin') || $u->isAbleTo('create-supplier');
        } catch (\Throwable) {
            return false;
        }
    }

    public function rules(): array
    {
        return [
            'firstName' => ['required', 'string', 'max:191'],
            'lastName' => ['nullable', 'string', 'max:191'],
            'email' => ['nullable', 'email', 'max:191'],
            'phoneNumber' => ['nullable', 'string', 'max:64'],
            'whatsappNumber' => ['nullable', 'string', 'max:64'],
            'address' => ['nullable', 'string', 'max:191'],
            'companyName' => ['nullable', 'string', 'max:191'],
            'companyAddress' => ['nullable', 'string', 'max:191'],
        ];
    }
}
