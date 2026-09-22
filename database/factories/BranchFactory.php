<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class BranchFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->company().' Branch',
            'code' => strtoupper($this->faker->unique()->lexify('BR-???')),
            'location' => $this->faker->city(),
            'is_active' => true,
        ];
    }
}