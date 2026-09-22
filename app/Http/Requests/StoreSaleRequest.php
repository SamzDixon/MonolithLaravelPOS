<?php

namespace App\Http\Requests;

use App\Models\Store;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSaleRequest extends FormRequest
{
    /**
     * A store manager can only record sales for their own store. A branch
     * manager can record for any store in their branch. Admin, anywhere.
     * Enforcing this here — before validation — means an unauthorised
     * request never touches the database at all.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        if (! $user) {
            return false;
        }

        $storeId = $this->input('store_id');
        if (! $storeId) {
            return false;
        }

        $store = Store::find($storeId);
        if (! $store) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isBranchManager()) {
            return $store->branch_id === $user->branch_id;
        }

        if ($user->isStoreManager()) {
            return $store->id === $user->store_id;
        }

        return false;
    }

    public function rules(): array
    {
        return [
            'store_id' => ['required', 'integer', 'exists:stores,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => [
                'required', 'integer',
                Rule::exists('products', 'id')->where('is_active', true),
            ],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'A sale must contain at least one item.',
            'items.*.quantity.min' => 'Each item must have a quantity of at least 1.',
        ];
    }
}