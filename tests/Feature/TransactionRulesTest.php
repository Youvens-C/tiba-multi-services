<?php

namespace Tests\Feature;

use App\Models\ServiceFee;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\PrepareTiba;
use Tests\TestCase;

/**
 * Règles sensibles des transactions : frais, plafonds, mouvements sans frais,
 * contrôle des numéros, annulation et droits d'accès.
 */
class TransactionRulesTest extends TestCase
{
    use RefreshDatabase, PrepareTiba;

    protected function setUp(): void
    {
        parent::setUp();
        $this->prepareTiba();
    }

    private function submit(array $data)
    {
        return $this->actingAs($this->employe)->post(route('transactions.store'), $data);
    }

    // ── Frais ─────────────────────────────────────────────────────────────

    public function test_retrait_moncash_applique_le_bareme(): void
    {
        $this->submit(['service' => 'moncash', 'movement_type' => 'retrait', 'client_phone' => '37123456', 'amount' => 1000])
            ->assertRedirect(route('transactions.index'));

        $t = Transaction::firstOrFail();
        $this->assertEquals(65, $t->fee_client);   // tranche 1000-1999
        $this->assertEquals(65, $t->fee_net);
        $this->assertSame('HTG', $t->currency);
    }

    public function test_transfert_natcash_a_un_frais_net_nul(): void
    {
        $this->submit(['service' => 'natcash', 'movement_type' => 'transfert', 'client_phone' => '32123456', 'amount' => 1000])
            ->assertRedirect(route('transactions.index'));

        $t = Transaction::firstOrFail();
        $this->assertEquals(ServiceFee::findFee('natcash', 'transfert', 1000), $t->fee_client);
        $this->assertEquals(0, $t->fee_net);
    }

    public function test_zelle_frais_10_pour_cent_et_net_5_pour_cent(): void
    {
        $this->submit(['service' => 'zelle', 'movement_type' => 'transfert', 'client_phone' => '2125551234', 'amount' => 100])
            ->assertRedirect(route('transactions.index'));

        $t = Transaction::firstOrFail();
        $this->assertEquals(10, $t->fee_client);
        $this->assertEquals(5, $t->fee_net);
        $this->assertSame('USD', $t->currency);
    }

    public function test_mouvements_sans_frais(): void
    {
        $this->submit(['service' => 'moncash', 'movement_type' => 'pap_padap', 'client_phone' => '37123456', 'amount' => 10])
            ->assertRedirect(route('transactions.index'));

        $t = Transaction::firstOrFail();
        $this->assertEquals(0, $t->fee_client);
        $this->assertEquals(0, $t->fee_net);
    }

    public function test_mouvement_sans_frais_sous_le_minimum_est_refuse(): void
    {
        $this->submit(['service' => 'moncash', 'movement_type' => 'pap_padap', 'client_phone' => '37123456', 'amount' => 9])
            ->assertSessionHas('error');
        $this->assertSame(0, Transaction::count());
    }

    // ── Plafonds ──────────────────────────────────────────────────────────

    public function test_plafond_moncash_74999(): void
    {
        $this->submit(['service' => 'moncash', 'movement_type' => 'retrait', 'client_phone' => '37123456', 'amount' => 75000])
            ->assertSessionHas('error');
        $this->assertSame(0, Transaction::count());

        $this->submit(['service' => 'moncash', 'movement_type' => 'retrait', 'client_phone' => '37123456', 'amount' => 74999])
            ->assertSessionMissing('error');
        $this->assertSame(1, Transaction::count());
    }

    public function test_plafond_natcash_99999(): void
    {
        $this->submit(['service' => 'natcash', 'movement_type' => 'retrait', 'client_phone' => '32123456', 'amount' => 100000])
            ->assertSessionHas('error');
        $this->assertSame(0, Transaction::count());
    }

    // ── Numéros et cohérence service / mouvement ──────────────────────────

    public function test_prefixe_moncash_refuse_sur_natcash(): void
    {
        $this->submit(['service' => 'natcash', 'movement_type' => 'retrait', 'client_phone' => '37123456', 'amount' => 500])
            ->assertSessionHas('error');
        $this->assertSame(0, Transaction::count());
    }

    public function test_numeros_invalides_refuses(): void
    {
        foreach (['-33567890', '.33567890', '3712345', 'abcdefgh'] as $phone) {
            $this->submit(['service' => 'moncash', 'movement_type' => 'retrait', 'client_phone' => $phone, 'amount' => 500]);
        }
        $this->submit(['service' => 'zelle', 'movement_type' => 'transfert', 'client_phone' => '-1234567890', 'amount' => 50]);

        $this->assertSame(0, Transaction::count());
    }

    public function test_zelle_enregistre_le_numero_sans_indicatif(): void
    {
        $this->submit(['service' => 'zelle', 'movement_type' => 'transfert', 'client_phone' => '+1 (212) 555-1234', 'amount' => 50]);

        $this->assertSame('2125551234', Transaction::firstOrFail()->client_phone);
    }

    public function test_zelle_n_accepte_que_le_transfert(): void
    {
        $this->submit(['service' => 'zelle', 'movement_type' => 'retrait', 'client_phone' => '2125551234', 'amount' => 50])
            ->assertSessionHasErrors('movement_type');
        $this->assertSame(0, Transaction::count());
    }

    // ── Annulation et visibilité ──────────────────────────────────────────

    public function test_seul_le_directeur_annule(): void
    {
        $t = $this->makeTransaction();

        $this->actingAs($this->employe)->post(route('transactions.cancel', $t))->assertForbidden();
        $this->assertSame('validee', $t->fresh()->status);

        $this->actingAs($this->directeur)->post(route('transactions.cancel', $t))->assertRedirect();
        $t->refresh();
        $this->assertSame('annulee', $t->status);
        $this->assertSame($this->directeur->id, $t->cancelled_by);
    }

    public function test_annulation_est_tracee_dans_le_journal(): void
    {
        $t = $this->makeTransaction();
        $this->actingAs($this->directeur)->post(route('transactions.cancel', $t));

        $this->assertDatabaseHas('audit_logs', ['action_type' => 'annulation_transaction', 'entity_id' => $t->id]);
    }

    public function test_employe_ne_voit_que_ses_transactions(): void
    {
        $autre = \App\Models\User::factory()->create();
        $this->makeTransaction(['client_phone' => '37111111']);
        $this->makeTransaction(['client_phone' => '37222222', 'created_by' => $autre->id]);

        $this->actingAs($this->employe)->get(route('transactions.index'))
            ->assertSee('37111111')
            ->assertDontSee('37222222');

        $this->actingAs($this->directeur)->get(route('transactions.index'))
            ->assertSee('37111111')
            ->assertSee('37222222');
    }

    public function test_employe_ne_modifie_pas_la_transaction_d_un_autre(): void
    {
        $autre = \App\Models\User::factory()->create();
        $t = $this->makeTransaction(['created_by' => $autre->id]);

        $this->actingAs($this->employe)
            ->put(route('transactions.update', $t), ['client_phone' => '37999999', 'amount' => 500])
            ->assertForbidden();
    }

    public function test_transaction_annulee_n_est_plus_modifiable(): void
    {
        $t = $this->makeTransaction(['status' => 'annulee']);

        $this->assertFalse($t->canBeModifiedBy($this->directeur));
        $this->assertFalse($t->canBeCancelledBy($this->directeur));
    }

    public function test_invite_est_redirige_vers_la_connexion(): void
    {
        $this->get(route('transactions.index'))->assertRedirect(route('login'));
    }

    public function test_compte_desactive_est_bloque(): void
    {
        $u = \App\Models\User::factory()->desactive()->create();
        $this->actingAs($u)->get(route('transactions.index'))->assertRedirect(route('login'));
    }
}
