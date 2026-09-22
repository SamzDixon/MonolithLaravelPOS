<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        $user = $this->route('user');
        $userId = $user instanceof User ? $user->getKey() : $user;
        $role = $this->input('role');

        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => [
                'required', 'email', 'max:190',
                Rule::unique('users', 'email')
                    ->whereNull('deleted_at')
                    ->ignore($userId),
            ],
            // Password only required when provided (i.e. admin is resetting it).
            'password' => ['nullable', 'confirmed', Password::min(8)],
            'role' => ['required', Rule::in(['admin', 'branch_manager', 'store_manager'])],
            'branch_id' => [
                Rule::requiredIf(in_array($role, ['branch_manager', 'store_manager'], true)),
                'nullable', 'integer', 'exists:branches,id',
            ],
            'store_id' => [
                Rule::requiredIf($role === 'store_manager'),
                'nullable', 'integer', 'exists:stores,id',
            ],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'A user with this email already exists.',
            'branch_id.required' => 'A branch is required for this role.',
            'store_id.required' => 'A store is required for a store manager.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'branch_id' => $this->input('role') === 'admin' ? null : $this->input('branch_id'),
            'store_id' => in_array($this->input('role'), ['admin', 'branch_manager'], true)
                ? null
                : $this->input('store_id'),
        ]);
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->input('branch_id') && $this->input('store_id')) {
                $storeBelongsToBranch = \App\Models\Store::where('id', $this->input('store_id'))
                    ->where('branch_id', $this->input('branch_id'))
                    ->exists();

                if (! $storeBelongsToBranch) {
                    $validator->errors()->add('store_id', 'The selected store does not belong to the selected branch.');
                }
            }
        });
    }
}