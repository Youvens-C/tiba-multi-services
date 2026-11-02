<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict');
            $table->string('action_type', 60);
            // annulation_transaction, modification_dette, annulation_paiement,
            // abandon_dette, annulation_dette, ajustement_stock,
            // modification_prix, creation_utilisateur, modification_role,
            // desactivation_compte, reactivation_compte, reinitialisation_mdp

            $table->string('entity_type', 40);
            // transaction, dette, paiement, produit, utilisateur

            $table->unsignedBigInteger('entity_id');
            $table->text('details')->nullable();
            $table->timestamp('created_at');

            // Index pour les recherches
            $table->index('user_id');
            $table->index('action_type');
            $table->index('entity_type');
            $table->index('created_at');

            // Index composé pour les recherches fréquentes
            $table->index(['entity_type', 'entity_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
