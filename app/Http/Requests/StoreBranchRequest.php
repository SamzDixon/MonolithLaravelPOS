<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required', 'string', 'max:120',
                Rule::unique('branches', 'name')->whereNull('deleted_at'),
            ],
            'code' => [
                'required', 'string', 'max:20',
                Rule::unique('branches', 'code')->whereNull('deleted_at'),
            ],
            'location' => ['nullable', 'string', 'max:120'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'A branch with this name already exists.',
            'code.unique' => 'This code is already in use.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['is_active' => $this->boolean('is_active')]);
    }
}