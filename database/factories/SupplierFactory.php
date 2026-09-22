<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class SupplierFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->company(),
            'contact_person' => $this->faker->name(),
            'phone' => '+2547'.$this->faker->numerify('########'),
            'email' => $this->faker->unique()->companyEmail(),
            'address' => $this->faker->city().', Kenya',
            'is_active' => true,
        ];
    }
}