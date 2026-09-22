<?php

namespace App\Policies;

use App\Models\StockReceipt;
use App\Models\User;

class StockReceiptPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function view(User $user, StockReceipt $receipt): bool
    {
        if (! $user->is_active) return false;
        if ($user->isAdmin()) return true;

        if ($user->isBranchManager()) {
            return $receipt->store->branch_id === $user->branch_id;
        }

        if ($user->isStoreManager()) {
            return $receipt->store_id === $user->store_id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        // Anyone who can see a store can receive stock into it.
        // The store-scope check is in the FormRequest.
        return $user->is_active
            && ($user->isAdmin() || $user->isBranchManager() || $user->isStoreManager());
    }

    public function delete(User $user, StockReceipt $receipt): bool
    {
        // Reversing a receipt is a supervisory action.
        if (! $user->is_active) return false;
        if ($user->isAdmin()) return true;

        if ($user->isBranchManager()) {
            return $receipt->store->branch_id === $user->branch_id;
        }

        return false;
    }
}