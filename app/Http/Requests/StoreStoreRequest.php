<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user && ($user->isAdmin() || $user->isBranchManager());
    }

    public function rules(): array
    {
        return [
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'name' => [
                'required', 'string', 'max:120',
                Rule::unique('stores', 'name')
                    ->where('branch_id', $this->input('branch_id'))
                    ->whereNull('deleted_at'),
            ],
            'code' => [
                'required', 'string', 'max:20',
                Rule::unique('stores', 'code')->whereNull('deleted_at'),
            ],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'A store with this name already exists in the selected branch.',
            'code.unique' => 'This store code is already in use.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['is_active' => $this->boolean('is_active')]);
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $user = $this->user();

            // Branch managers can only create stores in their own branch.
            if ($user->isBranchManager() && $this->input('branch_id') != $user->branch_id) {
                $validator->errors()->add('branch_id', 'You can only create stores in your own branch.');
            }
        });
    }
}