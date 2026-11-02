<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Décision (Option B, discutée avec le directeur) : le solde d'ouverture
 * devient modifiable dans une fenêtre de 5 minutes après sa saisie initiale,
 * contrairement au principe d'immuabilité prévu initialement dans le
 * dictionnaire de données pour cette table. On ajoute donc updated_at.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_opening_balances', function (Blueprint $table) {
            $table->timestamp('updated_at')->nullable()->after('created_at');
        });
    }

    public function down(): void
    {
        Schema::table('daily_opening_balances', function (Blueprint $table) {
            $table->dropColumn('updated_at');
        });
    }
};
