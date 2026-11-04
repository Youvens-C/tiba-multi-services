<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Annulation d'une vente d'accessoire (directeur uniquement) : la vente reste
 * en base avec le statut « annulee » (RG12 : jamais de suppression) et le
 * stock est restitué. Les ventes existantes sont toutes « validee ».
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accessory_sales', function (Blueprint $table) {
            $table->enum('status', ['validee', 'annulee'])->default('validee')->after('sold_by');
            $table->foreignId('cancelled_by')->nullable()->after('status')->constrained('users')->onDelete('restrict');
            $table->timestamp('cancelled_at')->nullable()->after('cancelled_by');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('accessory_sales', function (Blueprint $table) {
            $table->dropForeign(['cancelled_by']);
            $table->dropIndex(['status']);
            $table->dropColumn(['status', 'cancelled_by', 'cancelled_at']);
        });
    }
};
