<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            ['sku' => 'SUG-2KG', 'name' => 'Sugar 2kg', 'unit' => 'pkt', 'cost_price' => 180, 'selling_price' => 220, 'reorder_level' => 20],
            ['sku' => 'RICE-5KG', 'name' => 'Rice 5kg', 'unit' => 'bale', 'cost_price' => 750, 'selling_price' => 900, 'reorder_level' => 10],
            ['sku' => 'OIL-3L', 'name' => 'Cooking Oil 3L', 'unit' => 'bottle', 'cost_price' => 550, 'selling_price' => 680, 'reorder_level' => 15],
            ['sku' => 'MAI-2KG', 'name' => 'Maize Flour 2kg', 'unit' => 'pkt', 'cost_price' => 120, 'selling_price' => 150, 'reorder_level' => 30],
            ['sku' => 'MILK-500ML', 'name' => 'Milk 500ml', 'unit' => 'pkt', 'cost_price' => 45, 'selling_price' => 60, 'reorder_level' => 50],
            ['sku' => 'SOAP-BAR', 'name' => 'Bar Soap 800g', 'unit' => 'bar', 'cost_price' => 180, 'selling_price' => 230, 'reorder_level' => 20],
            ['sku' => 'TEA-250G', 'name' => 'Tea Leaves 250g', 'unit' => 'pkt', 'cost_price' => 200, 'selling_price' => 260, 'reorder_level' => 15],
            ['sku' => 'SALT-1KG', 'name' => 'Salt 1kg', 'unit' => 'pkt', 'cost_price' => 25, 'selling_price' => 40, 'reorder_level' => 40],
        ];

        foreach ($products as $product) {
            Product::firstOrCreate(
                ['sku' => $product['sku']],
                array_merge($product, ['is_active' => true])
            );
        }
    }
}