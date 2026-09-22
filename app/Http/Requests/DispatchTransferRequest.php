<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DispatchTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $transfer = $this->route('transfer');

        if (! $user || ! $transfer) {
            return false;
        }

        return $user->can('dispatch', $transfer);
    }

    public function rules(): array
    {
        return [];
    }
}