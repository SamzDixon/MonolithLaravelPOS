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

    public function test_full_transfer_lifecycle_moves_stock_between_stores(): void
    {
        [$storeA, $storeB, $product, $managerA, $managerB, $branchManager] = $this->seedScenario();

        // Branch manager initiates the transfer.
        $this->actingAs($branchManager)->post('/transfers', [
            'from_store_id' => $storeA->id,
            'to_store_id' => $storeB->id,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 5],
            ],
        ])->assertRedirect();

        $transfer = StockTransfer::firstOrFail();
        $this->assertEquals('pending', $transfer->status);
        $this->assertEquals(10, StockLevel::where('store_id', $storeA->id)
            ->where('product_id', $product->id)->value('quantity'));

        // Source store manager confirms the dispatch.
        $this->actingAs($managerA)
            ->post("/transfers/{$transfer->id}/dispatch")
            ->assertRedirect();

        $transfer->refresh();
        $this->assertEquals('dispatched', $transfer->status);
        $this->assertEquals(5, StockLevel::where('store_id', $storeA->id)
            ->where('product_id', $product->id)->value('quantity'));

        // Destination store manager confirms receipt.
        $this->actingAs($managerB)
            ->post("/transfers/{$transfer->id}/receive")
            ->assertRedirect();

        $transfer->refresh();
        $this->assertEquals('received', $transfer->status);
        $this->assertEquals(5, StockLevel::where('store_id', $storeB->id)
            ->where('product_id', $product->id)->value('quantity'));

        // Ledger has both legs.
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

    public function test_dispatch_fails_when_source_store_has_insufficient_stock(): void
    {
        [$storeA, $storeB, $product, $managerA, $managerB, $branchManager] = $this->seedScenario();

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
        $this->assertEquals(10, StockLevel::where('store_id', $storeA->id)
            ->where('product_id', $product->id)->value('quantity'));
    }

    public function test_transfer_cannot_be_received_twice(): void
    {
        [$storeA, $storeB, $product, $managerA, $managerB, $branchManager] = $this->seedScenario();

        $this->actingAs($branchManager)->post('/transfers', [
            'from_store_id' => $storeA->id,
            'to_store_id' => $storeB->id,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 3],
            ],
        ]);

        $transfer = StockTransfer::firstOrFail();

        $this->actingAs($managerA)->post("/transfers/{$transfer->id}/dispatch");
        $this->actingAs($managerB)->post("/transfers/{$transfer->id}/receive");

        $this->assertEquals(3, StockLevel::where('store_id', $storeB->id)
            ->where('product_id', $product->id)->value('quantity'));

        // Second receive attempt is rejected — the policy sees the status
        // is no longer 'dispatched' and returns false, which surfaces as
        // a 403 from the FormRequest's authorize() method.
        $this->actingAs($managerB)
            ->post("/transfers/{$transfer->id}/receive")
            ->assertForbidden();

        $this->assertEquals(3, StockLevel::where('store_id', $storeB->id)
            ->where('product_id', $product->id)->value('quantity'));
    }

    private function seedScenario(): array
    {
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

        return [$storeA, $storeB, $product, $managerA, $managerB, $branchManager];
    }
}