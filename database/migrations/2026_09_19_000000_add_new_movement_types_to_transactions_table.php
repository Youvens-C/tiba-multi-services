<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Ajoute 5 nouveaux types de mouvement sans frais, demandés par le directeur,
 * pour le suivi du solde théorique (CDC section 4.2) :
 * - pap_padap      : recharge crédit/forfait Digicel via Moncash (Moncash uniquement)
 * - lapoula        : recharge crédit/forfait Natcom via Natcash (Natcash uniquement)
 * - depot_paryaj_pam, depot_paryaj_lakay, depot_lotto_mobile : dépôts vers des
 *   plateformes tierces (borlette/loterie), disponibles pour Moncash et Natcash
 *
 * Les 5 diminuent le solde mobile de l'agent (même sens qu'un Transfert/Dépôt).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE transactions MODIFY movement_type ENUM(
            'retrait', 'transfert', 'depot',
            'pap_padap', 'lapoula',
            'depot_paryaj_pam', 'depot_paryaj_lakay', 'depot_lotto_mobile'
        ) NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE transactions MODIFY movement_type ENUM(
            'retrait', 'transfert', 'depot'
        ) NOT NULL");
    }
};
