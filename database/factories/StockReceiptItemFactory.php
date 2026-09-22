<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\StockReceipt;
use Illuminate\Database\Eloquent\Factories\Factory;

class StockReceiptItemFactory extends Factory
{
    public function definition(): array
    {
        $qty = $this->faker->numberBetween(10, 100);
        $cost = $this->faker->numberBetween(50, 500);

        return [
            'stock_receipt_id' => StockReceipt::factory(),
            'product_id' => Product::factory(),
            'quantity' => $qty,
            'unit_cost' => $cost,
            'line_total' => $qty * $cost,
        ];
    }
}