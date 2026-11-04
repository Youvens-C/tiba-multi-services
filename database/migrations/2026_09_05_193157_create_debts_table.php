<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('debts', function (Blueprint $table) {
            $table->id();
            $table->string('debtor_name', 150);
            $table->string('reason', 255);
            $table->decimal('initial_amount', 10, 2);
            $table->enum('currency', ['HTG', 'USD']);
            $table->decimal('remaining_balance', 10, 2);
            $table->enum('status', ['active', 'soldee', 'abandonnee', 'annulee'])->default('active');
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            // Index
            $table->index('debtor_name');
            $table->index('status');
            $table->index('currency');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('debts');
    }
};
