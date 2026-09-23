<?php

namespace App\Policies;

use App\Models\Supplier;
use App\Models\User;

class SupplierPolicy
{
    public function viewAny(User $user): bool
    {
        return (bool) $user->is_active;
    }

    public function view(User $user, Supplier $supplier): bool
    {
        return (bool) $user->is_active;
    }

    /**
     * Store managers can add suppliers they encounter in day-to-day
     * operations. Business-wide uniqueness on name, phone, and email
     * prevents duplicate records across stores.
     */
    public function create(User $user): bool
    {
        if (! $user->is_active) return false;

        return $user->isAdmin()
            || $user->isBranchManager()
            || $user->isStoreManager();
    }

    public function update(User $user, Supplier $supplier): bool
    {
        return $this->create($user);
    }

    /**
     * Deleting a supplier is business-wide and cross-branch. A store
     * manager removing a supplier another branch relies on would break
     * their operations. Deactivate instead — that's available to everyone
     * through the update ability.
     */
    public function delete(User $user, Supplier $supplier): bool
    {
        return $user->is_active && $user->isAdmin();
    }
}