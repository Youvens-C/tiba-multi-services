<?php

namespace Database\Seeders;

use App\Models\OperatorPrefix;
use Illuminate\Database\Seeder;

class OperatorPrefixSeeder extends Seeder
{
    public function run(): void
    {
        // Digicel / Moncash
        $digicelPrefixes = ['31', '34', '36', '37', '38', '39', '44', '46', '47', '48', '49'];

        foreach ($digicelPrefixes as $prefix) {
            OperatorPrefix::create([
                'operator' => 'digicel',
                'service' => 'moncash',
                'prefix' => $prefix,
                'status' => 'actif',
            ]);
        }

        // Natcom / Natcash
        $natcomPrefixes = ['32', '33', '35', '40', '41', '42', '43', '55', '56', '58'];

        foreach ($natcomPrefixes as $prefix) {
            OperatorPrefix::create([
                'operator' => 'natcom',
                'service' => 'natcash',
                'prefix' => $prefix,
                'status' => 'actif',
            ]);
        }
    }
}
