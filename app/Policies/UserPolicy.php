<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, User $target): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, User $target): bool
    {
        if (! $user->isAdmin()) {
            return false;
        }

        // An admin cannot demote or deactivate themselves — otherwise a
        // single admin who changes their own role locks the whole system
        // out of administration.
        return $user->id !== $target->id;
    }

    public function delete(User $user, User $target): bool
    {
        if (! $user->isAdmin()) {
            return false;
        }

        // Can't delete yourself, and can't delete the last remaining admin.
        if ($user->id === $target->id) {
            return false;
        }

        if ($target->isAdmin()) {
            $adminCount = User::where('role', 'admin')->where('is_active', true)->count();
            return $adminCount > 1;
        }

        return true;
    }
}