<?php

namespace Database\Factories;

use App\Models\Store;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class StockReceiptFactory extends Factory
{
    public function definition(): array
    {
        return [
            'reference' => 'GRN-'.$this->faker->unique()->numerify('########-####'),
            'supplier_id' => Supplier::factory(),
            'store_id' => Store::factory(),
            'user_id' => User::factory(),
            'total_cost' => 0,
            'status' => 'received',
            'received_at' => now(),
        ];
    }
}