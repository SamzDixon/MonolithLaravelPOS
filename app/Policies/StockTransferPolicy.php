<?php

namespace App\Policies;

use App\Models\StockTransfer;
use App\Models\User;

class StockTransferPolicy
{
    /**
     * Determine whether the user can view the list of transfers.
     */
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    /**
     * Determine whether the user can view a specific transfer.
     */
    public function view(User $user, StockTransfer $stockTransfer): bool
    {
        if (! $user->is_active) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isBranchManager()) {
            return $stockTransfer->fromStore->branch_id === $user->branch_id
                || $stockTransfer->toStore->branch_id === $user->branch_id;
        }

        if ($user->isStoreManager()) {
            return $stockTransfer->from_store_id === $user->store_id
                || $stockTransfer->to_store_id === $user->store_id;
        }

        return false;
    }

    /**
     * Determine whether the user can create a transfer.
     *
     * Store Managers can request stock transfers.
     * Branch Managers and Administrators can create/initiate transfers.
     */
    public function create(User $user): bool
    {
        if (! $user->is_active) {
            return false;
        }

        return $user->isAdmin()
            || $user->isBranchManager();
    }

    /**
     * Determine whether the user can update a transfer.
     *
     * Transfers should only be editable while pending.
     */
    public function update(User $user, StockTransfer $stockTransfer): bool
    {
        if (! $user->is_active) {
            return false;
        }

        if ($stockTransfer->status !== 'pending') {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isBranchManager()) {
            return $stockTransfer->fromStore->branch_id === $user->branch_id
                || $stockTransfer->toStore->branch_id === $user->branch_id;
        }

        if ($user->isStoreManager()) {
            return $stockTransfer->from_store_id === $user->store_id
                || $stockTransfer->to_store_id === $user->store_id;
        }

        return false;
    }

    /**
     * Determine whether the user can dispatch a transfer.
     *
     * The source side must confirm that stock is leaving.
     */
    public function dispatch(User $user, StockTransfer $transfer): bool
    {
        if (! $user->is_active) return false;
        if ($transfer->status !== 'pending') return false;

        if ($user->isAdmin()) return true;

        if ($user->isBranchManager()) {
            return $transfer->fromStore->branch_id === $user->branch_id;
        }

        if ($user->isStoreManager()) {
            return $transfer->from_store_id === $user->store_id;
        }

        return false;
    }

    public function receive(User $user, StockTransfer $transfer): bool
    {
        if (! $user->is_active) return false;
        if ($transfer->status !== 'dispatched') return false;

        if ($user->isAdmin()) return true;

        if ($user->isBranchManager()) {
            return $transfer->toStore->branch_id === $user->branch_id;
        }

        if ($user->isStoreManager()) {
            return $transfer->to_store_id === $user->store_id;
        }

        return false;
    }

    /**
     * Determine whether the user can delete a transfer.
     *
     * Only pending transfers may be deleted.
     * Completed transfers are historical stock records.
     */
    public function delete(User $user, StockTransfer $stockTransfer): bool
    {
        if (! $user->is_active) {
            return false;
        }

        if ($stockTransfer->status !== 'pending') {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isBranchManager()) {
            return $stockTransfer->fromStore->branch_id === $user->branch_id
                || $stockTransfer->toStore->branch_id === $user->branch_id;
        }

        return false;
    }

    /**
     * Transfers are historical stock records and should not be restored.
     */
    public function restore(User $user, StockTransfer $stockTransfer): bool
    {
        return false;
    }

    /**
     * Transfers are historical stock records and should never be
     * permanently deleted.
     */
    public function forceDelete(User $user, StockTransfer $stockTransfer): bool
    {
        return false;
    }
}