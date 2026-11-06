<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    /** Utilisateur de test : l'appli n'a ni e-mail ni vérification d'e-mail (connexion par username). */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'username' => fake()->unique()->userName(),
            'password' => static::$password ??= Hash::make('Password@123'),
            'role' => 'employe',
            'status' => 'actif',
        ];
    }

    public function directeur(): static
    {
        return $this->state(fn () => ['role' => 'directeur']);
    }

    public function desactive(): static
    {
        return $this->state(fn () => ['status' => 'desactive']);
    }
}
