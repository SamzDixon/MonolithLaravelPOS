<?php

namespace App\Http\Requests;

use App\Models\Supplier;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        $supplier = $this->route('supplier');
        return $this->user()?->can('update', $supplier) ?? false;
    }

    public function rules(): array
    {
        $supplier = $this->route('supplier');
        $id = $supplier instanceof Supplier ? $supplier->getKey() : $supplier;

        return [
            'name' => ['required', 'string', 'max:150', Rule::unique('suppliers', 'name')->whereNull('deleted_at')->ignore($id)],
            'contact_person' => ['nullable', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:150'],
            'address' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return ['name.unique' => 'A supplier with this name already exists.'];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['is_active' => $this->boolean('is_active')]);
    }
}