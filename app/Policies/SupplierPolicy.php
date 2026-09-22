<?php

namespace App\Policies;

use App\Models\Supplier;
use App\Models\User;

class SupplierPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function view(User $user, Supplier $supplier): bool
    {
        return $user->is_active;
    }

    public function create(User $user): bool
    {
        return $user->is_active && ($user->isAdmin() || $user->isBranchManager());
    }

    public function update(User $user, Supplier $supplier): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, Supplier $supplier): bool
    {
        return $user->isAdmin();
    }
}