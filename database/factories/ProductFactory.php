<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'sku' => strtoupper($this->faker->unique()->bothify('SKU-####')),
            'name' => $this->faker->words(2, true),
            'unit' => 'pcs',
            'cost_price' => $this->faker->numberBetween(50, 500),
            'selling_price' => $this->faker->numberBetween(600, 1500),
            'reorder_level' => $this->faker->numberBetween(5, 20),
            'is_active' => true,
        ];
    }
}