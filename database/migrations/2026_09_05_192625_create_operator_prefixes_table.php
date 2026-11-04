<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operator_prefixes', function (Blueprint $table) {
            $table->id();
            $table->enum('operator', ['digicel', 'natcom']);
            $table->enum('service', ['moncash', 'natcash']);
            $table->string('prefix', 2)->unique();
            $table->enum('status', ['actif', 'inactif'])->default('actif');
            $table->timestamps();

            // (prefix est déjà indexé par unique())
            $table->index('service');
            $table->index('operator');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operator_prefixes');
    }
};
