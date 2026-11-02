<?php

namespace Database\Seeders;

use App\Models\ServiceFee;
use Illuminate\Database\Seeder;

class ServiceFeeSeeder extends Seeder
{
    public function run(): void
    {
        // ==========================================
        // MONCASH - TRANSFERT
        // ==========================================
        $moncashTransferts = [
            ['min' => 100, 'max' => 249, 'fee' => 10],
            ['min' => 250, 'max' => 499, 'fee' => 10],
            ['min' => 500, 'max' => 999, 'fee' => 10],
            ['min' => 1000, 'max' => 1999, 'fee' => 15],
            ['min' => 2000, 'max' => 3999, 'fee' => 25],
            ['min' => 4000, 'max' => 7999, 'fee' => 40],
            ['min' => 8000, 'max' => 11999, 'fee' => 65],
            ['min' => 12000, 'max' => 19999, 'fee' => 75],
            ['min' => 20000, 'max' => 39999, 'fee' => 95],
            ['min' => 40000, 'max' => 59999, 'fee' => 100],
            ['min' => 60000, 'max' => 74999, 'fee' => 100],
        ];

        foreach ($moncashTransferts as $data) {
            ServiceFee::create([
                'service' => 'moncash',
                'movement_type' => 'transfert',
                'min_amount' => $data['min'],
                'max_amount' => $data['max'],
                'fee' => $data['fee'],
                'status' => 'actif',
            ]);
        }

        // ==========================================
        // MONCASH - RETRAIT
        // ==========================================
        $moncashRetraits = [
            ['min' => 20, 'max' => 99, 'fee' => 0],
            ['min' => 100, 'max' => 249, 'fee' => 12],
            ['min' => 250, 'max' => 499, 'fee' => 15],
            ['min' => 500, 'max' => 999, 'fee' => 40],
            ['min' => 1000, 'max' => 1999, 'fee' => 65],
            ['min' => 2000, 'max' => 3999, 'fee' => 115],
            ['min' => 4000, 'max' => 7999, 'fee' => 185],
            ['min' => 8000, 'max' => 11999, 'fee' => 275],
            ['min' => 12000, 'max' => 19999, 'fee' => 380],
            ['min' => 20000, 'max' => 39999, 'fee' => 640],
            ['min' => 40000, 'max' => 59999, 'fee' => 1050],
            ['min' => 60000, 'max' => 74999, 'fee' => 1400],
        ];

        foreach ($moncashRetraits as $data) {
            ServiceFee::create([
                'service' => 'moncash',
                'movement_type' => 'retrait',
                'min_amount' => $data['min'],
                'max_amount' => $data['max'],
                'fee' => $data['fee'],
                'status' => 'actif',
            ]);
        }

        // ==========================================
        // NATCASH - TRANSFERT (GRATUIT)
        // ==========================================
        $natcashTransferts = [
            ['min' => 20, 'max' => 99, 'fee' => 0],
            ['min' => 100, 'max' => 249, 'fee' => 0],
            ['min' => 250, 'max' => 499, 'fee' => 0],
            ['min' => 500, 'max' => 999, 'fee' => 0],
            ['min' => 1000, 'max' => 1999, 'fee' => 0],
            ['min' => 2000, 'max' => 3999, 'fee' => 0],
            ['min' => 4000, 'max' => 7999, 'fee' => 0],
            ['min' => 8000, 'max' => 11999, 'fee' => 0],
            ['min' => 12000, 'max' => 19999, 'fee' => 0],
            ['min' => 20000, 'max' => 39999, 'fee' => 0],
            ['min' => 40000, 'max' => 59999, 'fee' => 0],
            ['min' => 60000, 'max' => 74999, 'fee' => 0],
            ['min' => 75000, 'max' => 99999, 'fee' => 0],
        ];

        foreach ($natcashTransferts as $data) {
            ServiceFee::create([
                'service' => 'natcash',
                'movement_type' => 'transfert',
                'min_amount' => $data['min'],
                'max_amount' => $data['max'],
                'fee' => $data['fee'],
                'status' => 'actif',
            ]);
        }

        // ==========================================
        // NATCASH - DÉPÔT
        // ==========================================
        $natcashDepots = [
            ['min' => 20, 'max' => 99, 'fee' => 1],
            ['min' => 100, 'max' => 249, 'fee' => 3],
            ['min' => 250, 'max' => 499, 'fee' => 4],
            ['min' => 500, 'max' => 999, 'fee' => 6],
            ['min' => 1000, 'max' => 1999, 'fee' => 12],
            ['min' => 2000, 'max' => 3999, 'fee' => 20],
            ['min' => 4000, 'max' => 7999, 'fee' => 28],
            ['min' => 8000, 'max' => 11999, 'fee' => 37],
            ['min' => 12000, 'max' => 19999, 'fee' => 49],
            ['min' => 20000, 'max' => 39999, 'fee' => 62],
            ['min' => 40000, 'max' => 59999, 'fee' => 84],
            ['min' => 60000, 'max' => 74999, 'fee' => 102],
            ['min' => 75000, 'max' => 99999, 'fee' => 108],
        ];

        foreach ($natcashDepots as $data) {
            ServiceFee::create([
                'service' => 'natcash',
                'movement_type' => 'depot',
                'min_amount' => $data['min'],
                'max_amount' => $data['max'],
                'fee' => $data['fee'],
                'status' => 'actif',
            ]);
        }

        // ==========================================
        // NATCASH - RETRAIT
        // ==========================================
        $natcashRetraits = [
            ['min' => 20, 'max' => 99, 'fee' => 1],
            ['min' => 100, 'max' => 249, 'fee' => 3],
            ['min' => 250, 'max' => 499, 'fee' => 5],
            ['min' => 500, 'max' => 999, 'fee' => 7],
            ['min' => 1000, 'max' => 1999, 'fee' => 15],
            ['min' => 2000, 'max' => 3999, 'fee' => 81],
            ['min' => 4000, 'max' => 7999, 'fee' => 130],
            ['min' => 8000, 'max' => 11999, 'fee' => 248],
            ['min' => 12000, 'max' => 19999, 'fee' => 342],
            ['min' => 20000, 'max' => 39999, 'fee' => 576],
            ['min' => 40000, 'max' => 59999, 'fee' => 945],
            ['min' => 60000, 'max' => 74999, 'fee' => 1260],
            ['min' => 75000, 'max' => 99999, 'fee' => 1440],
        ];

        foreach ($natcashRetraits as $data) {
            ServiceFee::create([
                'service' => 'natcash',
                'movement_type' => 'retrait',
                'min_amount' => $data['min'],
                'max_amount' => $data['max'],
                'fee' => $data['fee'],
                'status' => 'actif',
            ]);
        }
    }
}
