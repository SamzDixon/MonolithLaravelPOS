<?php

namespace App\Policies;

use App\Models\StockReceipt;
use App\Models\User;

class StockReceiptPolicy
{
    public function viewAny(User $user): bool
    {
        return (bool) $user->is_active;
    }

    public function view(User $user, StockReceipt $receipt): bool
    {
        if (! $user->is_active) return false;
        if ($user->isAdmin()) return true;

        if ($user->isBranchManager()) {
            return (int) optional($receipt->store)->branch_id === (int) $user->branch_id;
        }

        if ($user->isStoreManager()) {
            return (int) $receipt->store_id === (int) $user->store_id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        if (! $user->is_active) return false;

        return $user->isAdmin()
            || $user->isBranchManager()
            || $user->isStoreManager();
    }

    /**
     * Reversing a receipt removes stock from the store. It's a supervisory
     * action — the person who received the delivery shouldn't be the one
     * undoing it. Branch managers can reverse receipts in their branch;
     * admins can reverse anywhere.
     */
    public function delete(User $user, StockReceipt $receipt): bool
    {
        if (! $user->is_active) return false;
        if ($user->isAdmin()) return true;

        if ($user->isBranchManager()) {
            return (int) optional($receipt->store)->branch_id === (int) $user->branch_id;
        }

        return false;
    }
}