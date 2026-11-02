<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            // Identification lisible de l'élément concerné, figée au moment de l'action
            // (ex. « Transaction · Moncash · Retrait · 15 000,00 HTG · 21/10/2026 à 21:20 »).
            $table->string('entity_label', 255)->nullable()->after('entity_id');

            // Valeurs avant → après : {"amount": {"old": 15000, "new": 12000}}
            $table->json('changes')->nullable()->after('details');
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropColumn(['entity_label', 'changes']);
        });
    }
};
