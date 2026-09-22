<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        Branch::firstOrCreate(
            ['code' => 'BR-001'],
            ['name' => 'KK Wholesalers Nairobi', 'location' => 'Nairobi', 'is_active' => true]
        );

        Branch::firstOrCreate(
            ['code' => 'BR-002'],
            ['name' => 'KK Wholesalers Mombasa', 'location' => 'Mombasa', 'is_active' => true]
        );
    }
}