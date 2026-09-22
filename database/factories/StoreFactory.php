<?php

namespace Database\Factories;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;

class StoreFactory extends Factory
{
    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'name' => $this->faker->company().' Store',
            'code' => strtoupper($this->faker->unique()->lexify('ST-???')),
            'is_active' => true,
        ];
    }
}