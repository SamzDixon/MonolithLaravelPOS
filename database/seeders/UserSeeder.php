<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $password = Hash::make('password');

        $nairobi = Branch::where('code', 'BR-001')->firstOrFail();
        $mombasa = Branch::where('code', 'BR-002')->firstOrFail();

        $store1 = Store::where('code', 'ST-001')->firstOrFail();
        $store2 = Store::where('code', 'ST-002')->firstOrFail();
        $store3 = Store::where('code', 'ST-003')->firstOrFail();

        // Administrator — no branch, no store. Sees everything.
        User::firstOrCreate(
            ['email' => 'admin@kkwholesalers.co.ke'],
            [
                'name' => 'System Administrator',
                'password' => $password,
                'role' => 'admin',
                'branch_id' => null,
                'store_id' => null,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        // One branch manager per branch.
        User::firstOrCreate(
            ['email' => 'bm.nairobi@kkwholesalers.co.ke'],
            [
                'name' => 'Nairobi Branch Manager',
                'password' => $password,
                'role' => 'branch_manager',
                'branch_id' => $nairobi->id,
                'store_id' => null,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        User::firstOrCreate(
            ['email' => 'bm.mombasa@kkwholesalers.co.ke'],
            [
                'name' => 'Mombasa Branch Manager',
                'password' => $password,
                'role' => 'branch_manager',
                'branch_id' => $mombasa->id,
                'store_id' => null,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        // One store manager per store.
        User::firstOrCreate(
            ['email' => 'sm.nairobimain@kkwholesalers.co.ke'],
            [
                'name' => 'Nairobi Main Store Manager',
                'password' => $password,
                'role' => 'store_manager',
                'branch_id' => $nairobi->id,
                'store_id' => $store1->id,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        User::firstOrCreate(
            ['email' => 'sm.mombasamain@kkwholesalers.co.ke'],
            [
                'name' => 'Mombasa Main Store Manager',
                'password' => $password,
                'role' => 'store_manager',
                'branch_id' => $mombasa->id,
                'store_id' => $store2->id,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        User::firstOrCreate(
            ['email' => 'sm.mombasawarehouse@kkwholesalers.co.ke'],
            [
                'name' => 'Mombasa Warehouse Store Manager',
                'password' => $password,
                'role' => 'store_manager',
                'branch_id' => $mombasa->id,
                'store_id' => $store3->id,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
    }
}