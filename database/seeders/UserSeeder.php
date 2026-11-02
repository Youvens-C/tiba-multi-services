<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Directeur
        User::create([
            'name' => 'Dernier Joseph',
            'username' => 'directeur',
            'password' => Hash::make('Admin@123'),
                     'role' => 'directeur',
                     'status' => 'actif',
        ]);

        // Employé
        User::create([
            'name' => 'Monica Charles',
            'username' => 'employe',
            'password' => Hash::make('Employe@123'),
                     'role' => 'employe',
                     'status' => 'actif',
        ]);
    }
}
