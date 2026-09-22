<?php

namespace App\Http\Requests;

use App\Models\Store;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $store = $this->route('store');

        if (! $user || ! $store) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $user->isBranchManager() && $store->branch_id === $user->branch_id;
    }

    public function rules(): array
    {
        $store = $this->route('store');
        $storeId = $store instanceof Store ? $store->getKey() : $store;

        return [
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'name' => [
                'required', 'string', 'max:120',
                Rule::unique('stores', 'name')
                    ->where('branch_id', $this->input('branch_id'))
                    ->whereNull('deleted_at')
                    ->ignore($storeId),
            ],
            'code' => [
                'required', 'string', 'max:20',
                Rule::unique('stores', 'code')
                    ->whereNull('deleted_at')
                    ->ignore($storeId),
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
}