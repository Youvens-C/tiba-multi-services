<?php

namespace Database\Seeders;

use App\Models\Accessory;
use Illuminate\Database\Seeder;

class AccessorySeeder extends Seeder
{
    public function run(): void
    {
        Accessory::create([
            'name' => 'Chargeur USB-C',
            'description' => 'Chargeur rapide 20W',
            'unit_price' => 500,
            'stock_quantity' => 20,
            'low_stock_threshold' => 5,
            'status' => 'actif',
            'created_by' => 1,
        ]);

        Accessory::create([
            'name' => 'Écouteurs Bluetooth',
            'description' => 'Sans fil, batterie 8h',
            'unit_price' => 1200,
            'stock_quantity' => 10,
            'low_stock_threshold' => 5,
            'status' => 'actif',
            'created_by' => 1,
        ]);

        Accessory::create([
            'name' => 'Coque iPhone 15',
            'description' => 'Silicone, noir',
            'unit_price' => 350,
            'stock_quantity' => 25,
            'low_stock_threshold' => 5,
            'status' => 'actif',
            'created_by' => 1,
        ]);
    }
}
