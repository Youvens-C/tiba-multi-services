<?php

namespace Tests\Feature;

use App\Models\Debt;
use App\Models\DailyOpeningBalance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\PrepareTiba;
use Tests\TestCase;

/**
 * Solde théorique (CDC 4.2) et cycle de vie des dettes.
 */
class BalanceAndDebtTest extends TestCase
{
    use RefreshDatabase, PrepareTiba;

    protected function setUp(): void
    {
        parent::setUp();
        $this->prepareTiba();
    }

    // ── Solde théorique ───────────────────────────────────────────────────

    public function test_solde_theorique_ouverture_plus_retraits_moins_sorties(): void
    {
        DailyOpeningBalance::create([
            'service' => 'moncash', 'balance_date' => today(),
            'opening_balance' => 10000, 'entered_by' => $this->directeur->id,
        ]);

        $this->makeTransaction(['movement_type' => 'retrait',   'amount' => 2000]);   // +2000
        $this->makeTransaction(['movement_type' => 'transfert', 'amount' => 500]);    // -500
        $this->makeTransaction(['movement_type' => 'pap_padap', 'amount' => 100, 'fee_client' => 0, 'fee_net' => 0]); // -100
        $this->makeTransaction(['movement_type' => 'retrait',   'amount' => 9999, 'status' => 'annulee']);          // ignorée

        $balances = $this->actingAs($this->directeur)->get(route('dashboard'))->viewData('theoreticalBalances');

        $this->assertEquals(10000 + 2000 - 500 - 100, $balances['moncash']);
    }

    public function test_solde_ouverture_une_seule_fois_par_service_et_par_jour(): void
    {
        DailyOpeningBalance::create([
            'service' => 'moncash', 'balance_date' => today(),
            'opening_balance' => 1, 'entered_by' => $this->directeur->id,
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        DailyOpeningBalance::create([
            'service' => 'moncash', 'balance_date' => today(),
            'opening_balance' => 2, 'entered_by' => $this->directeur->id,
        ]);
    }

    // ── Dettes : droits ───────────────────────────────────────────────────

    public function test_dettes_reservees_au_directeur(): void
    {
        $this->actingAs($this->employe)->get(route('debts.index'))->assertForbidden();
        $this->actingAs($this->employe)->post(route('debts.store'), [
            'debtor_name' => 'X', 'reason' => 'Y', 'initial_amount' => 100, 'currency' => 'HTG',
        ])->assertForbidden();
        $this->assertSame(0, Debt::count());
    }

    private function newDebt(float $amount = 5000, string $cur = 'HTG'): Debt
    {
        $this->actingAs($this->directeur)->post(route('debts.store'), [
            'debtor_name' => 'Jean', 'reason' => 'Prêt', 'initial_amount' => $amount, 'currency' => $cur,
        ]);
        return Debt::firstOrFail();
    }

    // ── Dettes : cycle de vie ─────────────────────────────────────────────

    public function test_paiement_partiel_reduit_le_solde(): void
    {
        $d = $this->newDebt(5000);
        $this->actingAs($this->directeur)->post(route('debts.pay', $d), ['amount_paid' => 2000]);

        $d->refresh();
        $this->assertEquals(3000, $d->remaining_balance);
        $this->assertSame('active', $d->status);
    }

    public function test_paiement_superieur_solde_la_dette_et_rend_la_monnaie(): void
    {
        $d = $this->newDebt(5000);
        $this->actingAs($this->directeur)->post(route('debts.pay', $d), ['amount_paid' => 6000]);

        $d->refresh();
        $p = $d->payments()->firstOrFail();
        $this->assertSame('soldee', $d->status);
        $this->assertEquals(0, $d->remaining_balance);
        $this->assertEquals(5000, $p->amount_applied);
        $this->assertEquals(1000, $p->change_given);
        $this->assertNotNull($d->closed_at);
    }

    public function test_annuler_un_paiement_reactive_la_dette(): void
    {
        $d = $this->newDebt(5000);
        $this->actingAs($this->directeur)->post(route('debts.pay', $d), ['amount_paid' => 5000]);
        $this->assertSame('soldee', $d->fresh()->status);

        $payment = $d->payments()->firstOrFail();
        $this->actingAs($this->directeur)->delete(route('debts.cancel-payment', $payment->id));

        $d->refresh();
        $this->assertSame('active', $d->status);
        $this->assertEquals(5000, $d->remaining_balance);
        $this->assertSame('annule', $payment->fresh()->status);
    }

    public function test_dette_abandonnee_n_accepte_plus_de_paiement(): void
    {
        $d = $this->newDebt(5000);
        $this->actingAs($this->directeur)->post(route('debts.abandon', $d));
        $this->assertSame('abandonnee', $d->fresh()->status);

        $this->actingAs($this->directeur)->post(route('debts.pay', $d), ['amount_paid' => 100])
            ->assertSessionHas('error');
        // L'abandon remet le solde à 0 (perte actée) ; le paiement refusé n'y change rien.
        $this->assertEquals(0, $d->fresh()->remaining_balance);
        $this->assertSame(0, $d->payments()->count());
    }

    public function test_paiement_annule_sur_dette_abandonnee_refuse(): void
    {
        $d = $this->newDebt(5000);
        $this->actingAs($this->directeur)->post(route('debts.pay', $d), ['amount_paid' => 1000]);
        $payment = $d->payments()->firstOrFail();
        $this->actingAs($this->directeur)->post(route('debts.abandon', $d));

        $this->actingAs($this->directeur)->delete(route('debts.cancel-payment', $payment->id))
            ->assertSessionHas('error');
        $this->assertSame('valide', $payment->fresh()->status);
    }

    public function test_montant_de_paiement_invalide_refuse(): void
    {
        $d = $this->newDebt(5000);
        $this->actingAs($this->directeur)->post(route('debts.pay', $d), ['amount_paid' => 0])
            ->assertSessionHasErrors('amount_paid');
        $this->assertEquals(5000, $d->fresh()->remaining_balance);
    }
}
