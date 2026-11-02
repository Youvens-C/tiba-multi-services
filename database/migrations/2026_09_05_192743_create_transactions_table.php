<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->enum('service', ['moncash', 'natcash', 'zelle']);
            $table->enum('movement_type', ['retrait', 'transfert', 'depot']);
            $table->string('client_phone', 20);
            $table->decimal('amount', 10, 2);
            $table->decimal('fee_client', 10, 2);
            $table->decimal('fee_net', 10, 2);
            $table->enum('currency', ['HTG', 'USD']);
            $table->enum('status', ['validee', 'annulee'])->default('validee');

            // Préfixe détecté (optionnel, pour audit)
            $table->string('detected_prefix', 2)->nullable();

            // Clés étrangères
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->foreignId('last_updated_by')->nullable()->constrained('users')->onDelete('restrict');
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->onDelete('restrict');
            $table->timestamp('last_updated_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->timestamps();

            // Index pour les performances
            $table->index('created_at');
            $table->index(['service', 'status']);
            $table->index('created_by');
            $table->index('currency');
            $table->index('client_phone');
            $table->index('movement_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
