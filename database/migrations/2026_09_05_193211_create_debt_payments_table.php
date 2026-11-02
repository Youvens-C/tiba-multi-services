<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('debt_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('debt_id')->constrained('debts')->onDelete('restrict');
            $table->decimal('amount_paid', 10, 2);
            $table->decimal('amount_applied', 10, 2);
            $table->decimal('change_given', 10, 2)->default(0)->nullable();
            $table->enum('status', ['valide', 'annule'])->default('valide');
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->onDelete('restrict');
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('created_at');

            // Index
            $table->index('debt_id');
            $table->index('created_by');
            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('debt_payments');
    }
};
