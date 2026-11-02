<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_opening_balances', function (Blueprint $table) {
            $table->id();
            $table->enum('service', ['moncash', 'natcash']);
            $table->date('balance_date');
            $table->decimal('opening_balance', 10, 2)->default(0);
            $table->foreignId('entered_by')->constrained('users')->onDelete('restrict');
            $table->timestamp('created_at');

            // Contrainte unique : un seul solde par service et par jour
            $table->unique(['service', 'balance_date']);

            // Index pour les recherches
            $table->index('balance_date');
            $table->index('service');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_opening_balances');
    }
};
