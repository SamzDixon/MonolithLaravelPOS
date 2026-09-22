<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        $role = $this->input('role');

        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => [
                'required', 'email', 'max:190',
                Rule::unique('users', 'email')->whereNull('deleted_at'),
            ],
            'password' => ['required', 'confirmed', Password::min(8)],
            'role' => ['required', Rule::in(['admin', 'branch_manager', 'store_manager'])],

            // Branch is required for branch_manager and store_manager.
            'branch_id' => [
                Rule::requiredIf(in_array($role, ['branch_manager', 'store_manager'], true)),
                'nullable', 'integer', 'exists:branches,id',
            ],
            // Store is required only for store_manager.
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
            // Admin role ignores branch/store — null them out so they don't
            // leak in from the form if a user toggles role back and forth.
            'branch_id' => $this->input('role') === 'admin' ? null : $this->input('branch_id'),
            'store_id' => in_array($this->input('role'), ['admin', 'branch_manager'], true)
                ? null
                : $this->input('store_id'),
        ]);
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // If both branch and store are provided, the store must belong
            // to the branch. Otherwise you could assign a Nairobi branch to
            // a Mombasa store and bypass every policy.
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