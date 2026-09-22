<?php

namespace App\Http\Requests;

use App\Models\Store;
use Illuminate\Foundation\Http\FormRequest;

class StoreStockReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! $this->user()?->can('create', \App\Models\StockReceipt::class)) {
            return false;
        }

        // Store managers can only receive into their own store.
        $user = $this->user();
        $storeId = $this->input('store_id');
        if ($user->isStoreManager() && (int) $storeId !== (int) $user->store_id) {
            return false;
        }

        // Branch managers can only receive into stores in their branch.
        if ($user->isBranchManager()) {
            $store = Store::find($storeId);
            if (! $store || $store->branch_id !== $user->branch_id) {
                return false;
            }
        }

        return true;
    }

    public function rules(): array
    {
        return [
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'store_id' => ['required', 'integer', 'exists:stores,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0', 'max:9999999.99'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}