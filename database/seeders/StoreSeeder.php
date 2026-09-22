<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Store;
use Illuminate\Database\Seeder;

class StoreSeeder extends Seeder
{
    public function run(): void
    {
        $nairobi = Branch::where('code', 'BR-001')->firstOrFail();
        $mombasa = Branch::where('code', 'BR-002')->firstOrFail();

        // Branch 1 has 1 store.
        Store::firstOrCreate(
            ['code' => 'ST-001'],
            ['branch_id' => $nairobi->id, 'name' => 'Nairobi Main', 'is_active' => true]
        );

        // Branch 2 has 2 stores.
        Store::firstOrCreate(
            ['code' => 'ST-002'],
            ['branch_id' => $mombasa->id, 'name' => 'Mombasa Main', 'is_active' => true]
        );

        Store::firstOrCreate(
            ['code' => 'ST-003'],
            ['branch_id' => $mombasa->id, 'name' => 'Mombasa Warehouse', 'is_active' => true]
        );
    }
}