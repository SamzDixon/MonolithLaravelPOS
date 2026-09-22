<?php

namespace App\Policies;

use App\Models\Store;
use App\Models\User;

class StorePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isBranchManager();
    }

    public function view(User $user, Store $store): bool
    {
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

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isBranchManager();
    }

    public function update(User $user, Store $store): bool
    {
        return $this->view($user, $store) && ($user->isAdmin() || $user->isBranchManager());
    }

    public function delete(User $user, Store $store): bool
    {
        return $user->isAdmin() || ($user->isBranchManager() && $store->branch_id === $user->branch_id);
    }
}