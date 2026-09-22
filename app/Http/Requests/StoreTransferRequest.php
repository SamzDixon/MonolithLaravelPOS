<?php

namespace App\Http\Requests;

use App\Models\Store;
use Illuminate\Foundation\Http\FormRequest;

class StoreTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if (! $user) {
            return false;
        }

        return $user->isAdmin() || $user->isBranchManager();
    }

    public function rules(): array
    {
        return [
            'from_store_id' => ['required', 'integer', 'exists:stores,id', 'different:to_store_id'],
            'to_store_id' => ['required', 'integer', 'exists:stores,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $user = $this->user();

            if ($user->isBranchManager()) {
                $from = Store::find($this->input('from_store_id'));
                if ($from && $from->branch_id !== $user->branch_id) {
                    $validator->errors()->add('from_store_id', 'You can only transfer stock from your own branch.');
                }
            }
        });
    }
}