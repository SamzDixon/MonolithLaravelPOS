<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\Supplier::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required', 'string', 'max:150',
                Rule::unique('suppliers', 'name')->whereNull('deleted_at'),
            ],
            'contact_person' => ['nullable', 'string', 'max:120'],
            'phone' => [
                'nullable', 'string', 'max:40',
                Rule::unique('suppliers', 'phone')->whereNull('deleted_at'),
            ],
            'email' => [
                'nullable', 'email', 'max:150',
                Rule::unique('suppliers', 'email')->whereNull('deleted_at'),
            ],
            'address' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'A supplier with this name already exists.',
            'phone.unique' => 'This phone number is already registered to another supplier.',
            'email.unique' => 'This email is already registered to another supplier.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['is_active' => $this->boolean('is_active')]);
    }
}