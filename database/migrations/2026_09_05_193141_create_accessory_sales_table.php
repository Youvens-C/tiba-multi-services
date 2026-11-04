<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accessory_sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('accessory_id')->constrained('accessories')->onDelete('restrict');
            $table->integer('quantity')->unsigned(); // unsigned garantit que quantity >= 0
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total_amount', 10, 2);
            $table->foreignId('sold_by')->constrained('users')->onDelete('restrict');
            // Annulation par le directeur : la vente reste en base (jamais de suppression)
            $table->enum('status', ['validee', 'annulee'])->default('validee');
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->onDelete('restrict');
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('created_at');
            $table->timestamp('updated_at')->nullable(); // correction de quantité

            // Index
            // accessory_id / sold_by / cancelled_by : index créés par les clés étrangères
            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accessory_sales');
    }
};
