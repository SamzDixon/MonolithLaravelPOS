<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReceiveTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $transfer = $this->route('transfer');

        if (! $user || ! $transfer) {
            return false;
        }

        return $user->can('receive', $transfer);
    }

    public function rules(): array
    {
        return [
            'items' => ['nullable', 'array'],
            'items.*.id' => ['required_with:items', 'integer', 'exists:stock_transfer_items,id'],
            'items.*.received_quantity' => ['required_with:items', 'integer', 'min:0'],
        ];
    }
}