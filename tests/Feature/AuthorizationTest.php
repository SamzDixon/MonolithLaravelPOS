<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StockLevel;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A store manager must not see sales that belong to another store,
     * even if they somehow get hold of the sale ID. This is the security
     * contract that keeps one shop's numbers out of another shop's view.
     */
    public function test_store_manager_cannot_view_another_stores_sale(): void
    {
        $branch = Branch::factory()->create();

        $storeA = Store::factory()->create(['branch_id' => $branch->id]);
        $storeB = Store::factory()->create(['branch_id' => $branch->id]);

        $managerA = User::factory()->create([
            'role' => 'store_manager',
            'branch_id' => $branch->id,
            'store_id' => $storeA->id,
        ]);

        $saleInB = Sale::factory()->create(['store_id' => $storeB->id]);

        $this->actingAs($managerA)
            ->get("/sales/{$saleInB->id}")
            ->assertForbidden();
    }

    public function test_store_manager_can_view_their_own_stores_sale(): void
    {
        $branch = Branch::factory()->create();
        $store = Store::factory()->create(['branch_id' => $branch->id]);

        $manager = User::factory()->create([
            'role' => 'store_manager',
            'branch_id' => $branch->id,
            'store_id' => $store->id,
        ]);

        $sale = Sale::factory()->create(['store_id' => $store->id]);

        $this->actingAs($manager)
            ->get("/sales/{$sale->id}")
            ->assertOk();
    }

    /**
     * A branch manager oversees every store in their branch but no
     * other branch. Two branches, two managers, one sale each.
     */
    public function test_branch_manager_cannot_view_sales_from_another_branch(): void
    {
        $branchA = Branch::factory()->create();
        $branchB = Branch::factory()->create();

        $storeB = Store::factory()->create(['branch_id' => $branchB->id]);

        $managerA = User::factory()->create([
            'role' => 'branch_manager',
            'branch_id' => $branchA->id,
            'store_id' => null,
        ]);

        $saleInB = Sale::factory()->create(['store_id' => $storeB->id]);

        $this->actingAs($managerA)
            ->get("/sales/{$saleInB->id}")
            ->assertForbidden();
    }

    public function test_admin_can_view_any_sale(): void
    {
        $branch = Branch::factory()->create();
        $store = Store::factory()->create(['branch_id' => $branch->id]);
        $admin = User::factory()->create(['role' => 'admin']);
        $sale = Sale::factory()->create(['store_id' => $store->id]);

        $this->actingAs($admin)
            ->get("/sales/{$sale->id}")
            ->assertOk();
    }

    /**
     * Store managers record sales for their store. They cannot record
     * sales against a store they don't work at. This prevents a rogue
     * POST from decrementing another store's stock.
     */
    public function test_store_manager_cannot_record_sale_for_another_store(): void
    {
        $branch = Branch::factory()->create();
        $storeA = Store::factory()->create(['branch_id' => $branch->id]);
        $storeB = Store::factory()->create(['branch_id' => $branch->id]);

        $managerA = User::factory()->create([
            'role' => 'store_manager',
            'branch_id' => $branch->id,
            'store_id' => $storeA->id,
        ]);

        $product = Product::factory()->create();
        StockLevel::factory()->create([
            'store_id' => $storeB->id,
            'product_id' => $product->id,
            'quantity' => 10,
        ]);

        $this->actingAs($managerA)
            ->post('/sales', [
                'store_id' => $storeB->id,
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 1],
                ],
            ])
            ->assertForbidden();

        // Confirm the other store's stock was not touched.
        $this->assertDatabaseHas('stock_levels', [
            'store_id' => $storeB->id,
            'product_id' => $product->id,
            'quantity' => 10,
        ]);
    }
}