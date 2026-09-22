<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\StockLevel;
use App\Models\StockMovement;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Seeder;

class StockSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@kkwholesalers.co.ke')->firstOrFail();
        $stores = Store::all();
        $products = Product::all();

        foreach ($stores as $store) {
            foreach ($products as $product) {
                // Vary the opening quantity so the dashboard tells a story:
                // some stores are well-stocked, some are near reorder level.
                $quantity = match ($product->sku) {
                    'SUG-2KG' => 120,
                    'RICE-5KG' => 60,
                    'OIL-3L' => 45,
                    'MAI-2KG' => 200,
                    'MILK-500ML' => 25,   // below reorder_level of 50 — triggers low stock
                    'SOAP-BAR' => 80,
                    'TEA-250G' => 12,     // below reorder_level of 15 — triggers low stock
                    'SALT-1KG' => 150,
                    default => 50,
                };

                // Warehouse carries more of everything.
                if ($store->code === 'ST-003') {
                    $quantity = (int) ($quantity * 2.5);
                }

                StockLevel::updateOrCreate(
                    ['store_id' => $store->id, 'product_id' => $product->id],
                    ['quantity' => $quantity]
                );

                // Write a ledger entry so the movement history isn't empty.
                StockMovement::create([
                    'store_id' => $store->id,
                    'product_id' => $product->id,
                    'quantity_delta' => $quantity,
                    'type' => 'opening',
                    'user_id' => $admin->id,
                    'notes' => 'Opening stock',
                ]);
            }
        }
    }
}