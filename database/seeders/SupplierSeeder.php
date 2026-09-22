<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = [
            ['name' => 'Mombasa Sugar Distributors', 'contact_person' => 'Ali Hassan', 'phone' => '+254722100100', 'email' => 'sales@mombasasugar.co.ke', 'address' => 'Mombasa, Kenya'],
            ['name' => 'Kenya Rice Millers', 'contact_person' => 'Grace Wanjiru', 'phone' => '+254722200200', 'email' => 'orders@kenyaricemillers.co.ke', 'address' => 'Mwea, Kenya'],
            ['name' => 'Bidco Africa Ltd', 'contact_person' => 'Peter Otieno', 'phone' => '+254722300300', 'email' => 'distributors@bidcoafrica.com', 'address' => 'Thika, Kenya'],
            ['name' => 'Unga Holdings', 'contact_person' => 'Mary Njoroge', 'phone' => '+254722400400', 'email' => 'wholesale@unga.com', 'address' => 'Nairobi, Kenya'],
            ['name' => 'Brookside Dairy', 'contact_person' => 'Joseph Kamau', 'phone' => '+254722500500', 'email' => 'sales@brookside.co.ke', 'address' => 'Ruiru, Kenya'],
        ];

        foreach ($suppliers as $data) {
            Supplier::firstOrCreate(['name' => $data['name']], array_merge($data, ['is_active' => true]));
        }
    }
}