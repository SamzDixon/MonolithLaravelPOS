<?php

namespace Database\Factories;

use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SaleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'reference' => 'SALE-'.$this->faker->unique()->numerify('########-####'),
            'store_id' => Store::factory(),
            'user_id' => User::factory(),
            'total_amount' => 0,
            'status' => 'completed',
        ];
    }
}