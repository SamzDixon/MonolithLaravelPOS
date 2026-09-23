<?php

namespace App\Policies;

use App\Models\StockTransfer;
use App\Models\User;

class StockTransferPolicy
{
    public function viewAny(User $user): bool
    {
        return (bool) $user->is_active;
    }

    public function view(User $user, StockTransfer $transfer): bool
    {
        if (! $user->is_active) return false;
        if ($user->isAdmin()) return true;

        $fromBranchId = (int) optional($transfer->fromStore)->branch_id;
        $toBranchId = (int) optional($transfer->toStore)->branch_id;
        $userBranchId = (int) $user->branch_id;

        if ($user->isBranchManager()) {
            return $fromBranchId === $userBranchId || $toBranchId === $userBranchId;
        }

        if ($user->isStoreManager()) {
            $fromStoreId = (int) $transfer->from_store_id;
            $toStoreId = (int) $transfer->to_store_id;
            $userStoreId = (int) $user->store_id;

            return $fromStoreId === $userStoreId || $toStoreId === $userStoreId;
        }

        return false;
    }

    public function create(User $user): bool
    {
        if (! $user->is_active) return false;

        return $user->isAdmin() || $user->isBranchManager();
    }

    public function update(User $user, StockTransfer $transfer): bool
    {
        if (! $user->is_active) return false;
        if ($transfer->status !== 'pending') return false;
        if ($user->isAdmin()) return true;

        if ($user->isBranchManager()) {
            return (int) optional($transfer->fromStore)->branch_id === (int) $user->branch_id;
        }

        return false;
    }

    /**
     * The source-side user confirms the dispatch. Branch managers covering
     * the source branch are the operational supervisors for that store, so
     * they can dispatch as well as the source store manager.
     */
    public function dispatch(User $user, StockTransfer $transfer): bool
    {
        if (! $user->is_active) return false;
        if ($transfer->status !== 'pending') return false;
        if ($user->isAdmin()) return true;

        if ($user->isBranchManager()) {
            return (int) optional($transfer->fromStore)->branch_id === (int) $user->branch_id;
        }

        if ($user->isStoreManager()) {
            return (int) $transfer->from_store_id === (int) $user->store_id;
        }

        return false;
    }

    /**
     * The destination-side user confirms the receipt. Branch managers
     * covering the destination branch can receive on behalf of any store
     * in that branch — matching how the dispatch side works.
     */
    public function receive(User $user, StockTransfer $transfer): bool
    {
        if (! $user->is_active) return false;
        if ($transfer->status !== 'dispatched') return false;
        if ($user->isAdmin()) return true;

        if ($user->isBranchManager()) {
            return (int) optional($transfer->toStore)->branch_id === (int) $user->branch_id;
        }

        if ($user->isStoreManager()) {
            return (int) $transfer->to_store_id === (int) $user->store_id;
        }

        return false;
    }

    public function delete(User $user, StockTransfer $transfer): bool
    {
        if (! $user->is_active) return false;
        if ($transfer->status !== 'pending') return false;
        if ($user->isAdmin()) return true;

        if ($user->isBranchManager()) {
            $fromBranchId = (int) optional($transfer->fromStore)->branch_id;
            $toBranchId = (int) optional($transfer->toStore)->branch_id;
            $userBranchId = (int) $user->branch_id;

            return $fromBranchId === $userBranchId || $toBranchId === $userBranchId;
        }

        return false;
    }

    public function restore(User $user, StockTransfer $transfer): bool
    {
        return false;
    }

    public function forceDelete(User $user, StockTransfer $transfer): bool
    {
        return false;
    }
}