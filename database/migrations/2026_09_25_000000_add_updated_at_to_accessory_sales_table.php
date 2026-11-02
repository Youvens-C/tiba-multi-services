<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Permet la correction d'une vente d'accessoire mal saisie (quantité).
 * Même logique que daily_opening_balances : on sort de l'immuabilité stricte
 * prévue au dictionnaire, encadré par AccessorySale::canBeModifiedBy().
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accessory_sales', function (Blueprint $table) {
            $table->timestamp('updated_at')->nullable()->after('created_at');
        });
    }

    public function down(): void
    {
        Schema::table('accessory_sales', function (Blueprint $table) {
            $table->dropColumn('updated_at');
        });
    }
};
