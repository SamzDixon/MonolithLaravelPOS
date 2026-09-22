<?php

namespace App\Policies;

use App\Models\Sale;
use App\Models\User;

class SalePolicy
{
    /**
     * Determine whether the user can view the list of sales.
     */
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    /**
     * Determine whether the user can view a specific sale.
     */
    public function view(User $user, Sale $sale): bool
    {
        if (! $user->is_active) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isBranchManager()) {
            return $sale->store->branch_id === $user->branch_id;
        }

        if ($user->isStoreManager()) {
            return $sale->store_id === $user->store_id;
        }

        return false;
    }

    /**
     * Determine whether the user can create a sale.
     */
    public function create(User $user): bool
    {
        if (! $user->is_active) {
            return false;
        }

        return $user->isAdmin()
            || $user->isStoreManager();
    }

    /**
     * Determine whether the user can update a sale.
     */
    public function update(User $user, Sale $sale): bool
    {
        if (! $user->is_active) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        // Sales are treated as completed records.
        // Managers should not modify them after creation.
        return false;
    }

    /**
     * Determine whether the user can delete a sale.
     */
    public function delete(User $user, Sale $sale): bool
    {
        // Sales are historical financial/stock records.
        // Do not allow normal deletion.
        return false;
    }

    /**
     * Determine whether the user can restore a sale.
     */
    public function restore(User $user, Sale $sale): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete a sale.
     */
    public function forceDelete(User $user, Sale $sale): bool
    {
        return false;
    }
}