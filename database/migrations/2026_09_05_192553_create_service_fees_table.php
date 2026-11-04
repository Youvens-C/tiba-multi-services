<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_fees', function (Blueprint $table) {
            $table->id();
            $table->enum('service', ['moncash', 'natcash']);
            $table->enum('movement_type', ['retrait', 'transfert', 'depot']);
            $table->decimal('min_amount', 10, 2);
            $table->decimal('max_amount', 10, 2);
            $table->decimal('fee', 10, 2);
            $table->enum('status', ['actif', 'inactif'])->default('actif');
            $table->timestamps();

            // Index pour les recherches rapides
            $table->index(['service', 'movement_type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_fees');
    }
};
