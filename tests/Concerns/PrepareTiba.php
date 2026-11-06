<?php

namespace Tests\Concerns;

use App\Models\Transaction;
use App\Models\User;
use Database\Seeders\OperatorPrefixSeeder;
use Database\Seeders\ServiceFeeSeeder;

/**
 * Outillage commun aux tests fonctionnels : tarifs et préfixes réels (seeders),
 * un directeur, un employé, et un raccourci pour créer une transaction.
 */
trait PrepareTiba
{
    protected User $directeur;
    protected User $employe;

    protected function prepareTiba(): void
    {
        $this->seed(ServiceFeeSeeder::class);
        $this->seed(OperatorPrefixSeeder::class);
        $this->directeur = User::factory()->directeur()->create();
        $this->employe = User::factory()->create();
    }

    /** Insère directement une transaction (sans passer par le contrôleur). */
    protected function makeTransaction(array $attrs = []): Transaction
    {
        return Transaction::create($attrs + [
            'service' => 'moncash',
            'movement_type' => 'retrait',
            'client_phone' => '37123456',
            'amount' => 1000,
            'fee_client' => 65,
            'fee_net' => 65,
            'currency' => 'HTG',
            'status' => 'validee',
            'created_by' => $this->employe->id,
        ]);
    }
}
