<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Sale;
use App\Models\StockLevel;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * All stock mutations funnel through here. Nothing else touches stock_levels
 * or stock_movements directly. That rule is what keeps the numbers honest.
 */
class StockService
{
    /**
     * Record an opening stock entry. Used when stock first arrives at a store.
     * This is the only way to create a stock_levels row from nothing.
     */
    public function recordOpeningStock(Store $store, Product $product, int $quantity, User $user, ?string $notes = null): StockMovement
    {
        if ($quantity <= 0) {
            throw new RuntimeException('Opening stock must be positive.');
        }

        return DB::transaction(function () use ($store, $product, $quantity, $user, $notes) {
            $level = $this->lockLevel($store->id, $product->id);
            $level->quantity += $quantity;
            $level->save();

            return StockMovement::create([
                'store_id' => $store->id,
                'product_id' => $product->id,
                'quantity_delta' => $quantity,
                'type' => 'opening',
                'user_id' => $user->id,
                'notes' => $notes,
            ]);
        });
    }

    /**
     * Decrement stock for a sale. Locks the row so two concurrent sales
     * can't both think there are 5 units when there are only 5 total.
     */
    public function recordSale(Sale $sale, User $user): void
    {
        DB::transaction(function () use ($sale, $user) {
            foreach ($sale->items as $item) {
                $level = $this->lockLevel($sale->store_id, $item->product_id);

                if ($level->quantity < $item->quantity) {
                    throw new RuntimeException(
                        "Insufficient stock for {$item->product->name}. "
                        . "Available: {$level->quantity}, requested: {$item->quantity}."
                    );
                }

                $level->quantity -= $item->quantity;
                $level->save();

                StockMovement::create([
                    'store_id' => $sale->store_id,
                    'product_id' => $item->product_id,
                    'quantity_delta' => -$item->quantity,
                    'type' => 'sale',
                    'reference_type' => Sale::class,
                    'reference_id' => $sale->id,
                    'user_id' => $user->id,
                    'notes' => "Sale {$sale->reference}",
                ]);
            }
        });
    }

    /**
     * Dispatch a transfer. Stock leaves the source store now. It is NOT
     * yet at the destination — that only happens on receipt.
     */
    public function dispatchTransfer(StockTransfer $transfer, User $user): void
    {
        if ($transfer->status !== 'pending') {
            throw new RuntimeException('Only pending transfers can be dispatched.');
        }

        DB::transaction(function () use ($transfer, $user) {
            foreach ($transfer->items as $item) {
                $level = $this->lockLevel($transfer->from_store_id, $item->product_id);

                if ($level->quantity < $item->quantity) {
                    throw new RuntimeException(
                        "Cannot dispatch. {$item->product->name}: "
                        . "available {$level->quantity}, requested {$item->quantity}."
                    );
                }

                $level->quantity -= $item->quantity;
                $level->save();

                StockMovement::create([
                    'store_id' => $transfer->from_store_id,
                    'product_id' => $item->product_id,
                    'quantity_delta' => -$item->quantity,
                    'type' => 'transfer_out',
                    'reference_type' => StockTransfer::class,
                    'reference_id' => $transfer->id,
                    'user_id' => $user->id,
                    'notes' => "Transfer {$transfer->reference} dispatched",
                ]);
            }

            $transfer->update([
                'status' => 'dispatched',
                'dispatched_by' => $user->id,
                'dispatched_at' => now(),
            ]);
        });
    }

    /**
     * Receive a transfer at the destination. Received quantities can differ
     * from dispatched (damage, theft, miscount). We record what actually
     * arrived and let the discrepancy stand in the ledger.
     */
    public function receiveTransfer(StockTransfer $transfer, User $user): void
    {
        if ($transfer->status !== 'dispatched') {
            throw new RuntimeException('Only dispatched transfers can be received.');
        }

        DB::transaction(function () use ($transfer, $user) {
            foreach ($transfer->items as $item) {
                $received = $item->received_quantity ?? $item->quantity;

                $level = $this->lockLevel($transfer->to_store_id, $item->product_id);
                $level->quantity += $received;
                $level->save();

                StockMovement::create([
                    'store_id' => $transfer->to_store_id,
                    'product_id' => $item->product_id,
                    'quantity_delta' => $received,
                    'type' => 'transfer_in',
                    'reference_type' => StockTransfer::class,
                    'reference_id' => $transfer->id,
                    'user_id' => $user->id,
                    'notes' => "Transfer {$transfer->reference} received",
                ]);
            }

            $transfer->update([
                'status' => 'received',
                'received_by' => $user->id,
                'received_at' => now(),
            ]);
        });
    }

    /**
     * Manual adjustment for stock counts, write-offs, damages.
     * Positive or negative. Never zero.
     */
    public function adjustStock(Store $store, Product $product, int $delta, User $user, string $reason): StockMovement
    {
        if ($delta === 0) {
            throw new RuntimeException('Adjustment delta cannot be zero.');
        }

        return DB::transaction(function () use ($store, $product, $delta, $user, $reason) {
            $level = $this->lockLevel($store->id, $product->id);

            if ($level->quantity + $delta < 0) {
                throw new RuntimeException('Adjustment would result in negative stock.');
            }

            $level->quantity += $delta;
            $level->save();

            return StockMovement::create([
                'store_id' => $store->id,
                'product_id' => $product->id,
                'quantity_delta' => $delta,
                'type' => 'adjustment',
                'user_id' => $user->id,
                'notes' => $reason,
            ]);
        });
    }

    /**
     * Lock and return the stock_levels row for update. Creates it if missing.
     * The lockForUpdate is the whole point — without it, two cashiers selling
     * the last item both succeed and stock goes negative.
     */
    private function lockLevel(int $storeId, int $productId): StockLevel
    {
        $level = StockLevel::where('store_id', $storeId)
            ->where('product_id', $productId)
            ->lockForUpdate()
            ->first();

        if (! $level) {
            // Race-safe create. If two requests both miss, one wins the insert
            // and the other catches the unique constraint violation and re-reads.
            try {
                $level = StockLevel::create([
                    'store_id' => $storeId,
                    'product_id' => $productId,
                    'quantity' => 0,
                ]);
                $level = StockLevel::where('id', $level->id)->lockForUpdate()->first();
            } catch (\Illuminate\Database\QueryException $e) {
                $level = StockLevel::where('store_id', $storeId)
                    ->where('product_id', $productId)
                    ->lockForUpdate()
                    ->firstOrFail();
            }
        }

        return $level;
    }
}