<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\StockLevel;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaleRecordingTest extends TestCase
{
    use RefreshDatabase;

    public function test_recording_a_sale_decrements_store_stock(): void
    {
        $store = Store::factory()->create();
        $product = Product::factory()->create(['selling_price' => 100]);
        StockLevel::factory()->create([
            'store_id' => $store->id,
            'product_id' => $product->id,
            'quantity' => 10,
        ]);

        $manager = User::factory()->create([
            'role' => 'store_manager',
            'store_id' => $store->id,
        ]);

        $response = $this->actingAs($manager)->post('/sales', [
            'store_id' => $store->id,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 3],
            ],
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('stock_levels', [
            'store_id' => $store->id,
            'product_id' => $product->id,
            'quantity' => 7,
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'store_id' => $store->id,
            'product_id' => $product->id,
            'quantity_delta' => -3,
            'type' => 'sale',
        ]);
    }

    public function test_sale_is_rejected_when_stock_is_insufficient(): void
    {
        $store = Store::factory()->create();
        $product = Product::factory()->create();
        StockLevel::factory()->create([
            'store_id' => $store->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $manager = User::factory()->create([
            'role' => 'store_manager',
            'store_id' => $store->id,
        ]);

        $response = $this->actingAs($manager)->post('/sales', [
            'store_id' => $store->id,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 5],
            ],
        ]);

        $response->assertSessionHasErrors();

        // Stock must be untouched after a failed sale.
        $this->assertDatabaseHas('stock_levels', [
            'store_id' => $store->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $this->assertDatabaseMissing('sales', [
            'store_id' => $store->id,
        ]);
    }

    public function test_sale_reference_is_unique_and_human_readable(): void
    {
        $store = Store::factory()->create();
        $product = Product::factory()->create();
        StockLevel::factory()->create([
            'store_id' => $store->id,
            'product_id' => $product->id,
            'quantity' => 10,
        ]);

        $manager = User::factory()->create([
            'role' => 'store_manager',
            'store_id' => $store->id,
        ]);

        $this->actingAs($manager)->post('/sales', [
            'store_id' => $store->id,
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ]);

        $sale = \App\Models\Sale::firstOrFail();
        $this->assertStringStartsWith('SALE-'.now()->format('Ymd'), $sale->reference);
    }
}