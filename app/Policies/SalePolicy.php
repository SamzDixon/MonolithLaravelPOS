<?php

namespace App\Policies;

use App\Models\Sale;
use App\Models\User;

class SalePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function view(User $user, Sale $sale): bool
    {
        if (! $user->is_active) return false;
        if ($user->isAdmin()) return true;
        if ($user->isBranchManager()) {
            return $sale->store->branch_id === $user->branch_id;
        }
        if ($user->isStoreManager()) {
            return $sale->store_id === $user->store_id;
        }
        return false;
    }

    public function create(User $user): bool
    {
        if (! $user->is_active) return false;

        // Branch managers supervise stores and may cover a shift.
        // StoreSaleRequest still scopes them to their own branch.
        return $user->isAdmin()
            || $user->isBranchManager()
            || $user->isStoreManager();
    }

    /**
     * Voiding is modelled as the "delete" ability so it plugs into the
     * resource route convention. It does NOT delete the row — it sets
     * status=voided and returns stock. Store managers can't void their
     * own sales: separation of duties.
     */
    public function delete(User $user, Sale $sale): bool
    {
        if (! $user->is_active) return false;

        // Already voided — nothing to do.
        if ($sale->status === 'voided') return false;

        if ($user->isAdmin()) return true;

        if ($user->isBranchManager()) {
            return $sale->store->branch_id === $user->branch_id;
        }

        // Store managers deliberately excluded.
        return false;
    }

    public function update(User $user, Sale $sale): bool
    {
        return false;
    }

    public function restore(User $user, Sale $sale): bool
    {
        return false;
    }

    public function forceDelete(User $user, Sale $sale): bool
    {
        return false;
    }
}