<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Product;
use App\Models\StockLevel;
use App\Models\StockTransfer;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockTransferLifecycleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Full happy path: request, dispatch, receive. This exercises every
     * step of the transfer state machine and proves the ledger records
     * both the outbound and inbound movements.
     */
    public function test_full_transfer_lifecycle_moves_stock_between_stores(): void
    {
        $this->markTestIncomplete('StockTransferController not yet built.');

        [$branch, $storeA, $storeB, $product, $managerA, $managerB, $branchManager] = $this->seedScenario();

        // Request a transfer of 5 units from A to B.
        $this->actingAs($branchManager)->post('/transfers', [
            'from_store_id' => $storeA->id,
            'to_store_id' => $storeB->id,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 5],
            ],
        ])->assertRedirect();

        $transfer = StockTransfer::firstOrFail();
        $this->assertEquals('pending', $transfer->status);

        // Source store manager dispatches.
        $this->actingAs($managerA)
            ->post("/transfers/{$transfer->id}/dispatch")
            ->assertRedirect();

        $transfer->refresh();
        $this->assertEquals('dispatched', $transfer->status);
        $this->assertEquals(5, StockLevel::where('store_id', $storeA->id)
            ->where('product_id', $product->id)->value('quantity'));

        // Destination store manager receives.
        $this->actingAs($managerB)
            ->post("/transfers/{$transfer->id}/receive")
            ->assertRedirect();

        $transfer->refresh();
        $this->assertEquals('received', $transfer->status);
        $this->assertEquals(5, StockLevel::where('store_id', $storeB->id)
            ->where('product_id', $product->id)->value('quantity'));

        // Two ledger entries: out of A, into B.
        $this->assertDatabaseHas('stock_movements', [
            'store_id' => $storeA->id,
            'product_id' => $product->id,
            'quantity_delta' => -5,
            'type' => 'transfer_out',
        ]);
        $this->assertDatabaseHas('stock_movements', [
            'store_id' => $storeB->id,
            'product_id' => $product->id,
            'quantity_delta' => 5,
            'type' => 'transfer_in',
        ]);
    }

    /**
     * You cannot dispatch a transfer if the source store doesn't have
     * enough stock. The whole operation must fail atomically.
     */
    public function test_dispatch_fails_when_source_store_has_insufficient_stock(): void
    {
        $this->markTestIncomplete('StockTransferController not yet built.');
        [$branch, $storeA, $storeB, $product, $managerA, $managerB, $branchManager] = $this->seedScenario();

        $this->actingAs($branchManager)->post('/transfers', [
            'from_store_id' => $storeA->id,
            'to_store_id' => $storeB->id,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 100],
            ],
        ]);

        $transfer = StockTransfer::firstOrFail();

        $this->actingAs($managerA)
            ->post("/transfers/{$transfer->id}/dispatch")
            ->assertSessionHasErrors();

        $transfer->refresh();
        $this->assertEquals('pending', $transfer->status);

        // Stock at A is unchanged.
        $this->assertEquals(10, StockLevel::where('store_id', $storeA->id)
            ->where('product_id', $product->id)->value('quantity'));
    }

    /**
     * The same transfer cannot be received twice. Without this guard,
     * a double-click or a page refresh duplicates stock at the
     * destination — a bug that silently corrupts inventory.
     */
    public function test_transfer_cannot_be_received_twice(): void
    {
        $this->markTestIncomplete('StockTransferController not yet built.');
        [$branch, $storeA, $storeB, $product, $managerA, $managerB, $branchManager] = $this->seedScenario();

        $this->actingAs($branchManager)->post('/transfers', [
            'from_store_id' => $storeA->id,
            'to_store_id' => $storeB->id,
            'items' => [['product_id' => $product->id, 'quantity' => 3]],
        ]);
        $transfer = StockTransfer::firstOrFail();

        $this->actingAs($managerA)->post("/transfers/{$transfer->id}/dispatch");
        $this->actingAs($managerB)->post("/transfers/{$transfer->id}/receive");

        // Second receive attempt must not change anything.
        $this->actingAs($managerB)
            ->post("/transfers/{$transfer->id}/receive")
            ->assertSessionHasErrors();

        $this->assertEquals(3, StockLevel::where('store_id', $storeB->id)
            ->where('product_id', $product->id)->value('quantity'));
    }

    /**
     * Sets up two stores in the same branch, a product with 10 units at
     * store A and none at store B, and the three users needed to run
     * each step of the transfer flow.
     */
    private function seedScenario(): array
    {
        $this->markTestIncomplete('StockTransferController not yet built.');
        $branch = Branch::factory()->create();
        $storeA = Store::factory()->create(['branch_id' => $branch->id]);
        $storeB = Store::factory()->create(['branch_id' => $branch->id]);
        $product = Product::factory()->create();

        StockLevel::factory()->create([
            'store_id' => $storeA->id,
            'product_id' => $product->id,
            'quantity' => 10,
        ]);
        StockLevel::factory()->create([
            'store_id' => $storeB->id,
            'product_id' => $product->id,
            'quantity' => 0,
        ]);

        $managerA = User::factory()->create([
            'role' => 'store_manager',
            'branch_id' => $branch->id,
            'store_id' => $storeA->id,
        ]);
        $managerB = User::factory()->create([
            'role' => 'store_manager',
            'branch_id' => $branch->id,
            'store_id' => $storeB->id,
        ]);
        $branchManager = User::factory()->create([
            'role' => 'branch_manager',
            'branch_id' => $branch->id,
            'store_id' => null,
        ]);

        return [$branch, $storeA, $storeB, $product, $managerA, $managerB, $branchManager];
    }
}