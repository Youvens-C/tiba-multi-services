<?php

namespace Tests\Feature;

use App\Models\Accessory;
use App\Models\AccessorySale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\PrepareTiba;
use Tests\TestCase;

/**
 * Catalogue et ventes d'accessoires : unicité du nom, prix > 0, stock,
 * annulation réservée au directeur.
 */
class AccessoryRulesTest extends TestCase
{
    use RefreshDatabase, PrepareTiba;

    protected function setUp(): void
    {
        parent::setUp();
        $this->prepareTiba();
    }

    private function product(array $a = []): Accessory
    {
        return Accessory::create($a + [
            'name' => 'Chargeur', 'unit_price' => 250, 'stock_quantity' => 10,
            'low_stock_threshold' => 2, 'status' => 'actif', 'created_by' => $this->directeur->id,
        ]);
    }

    private function createProduct(array $data)
    {
        return $this->actingAs($this->directeur)->post(route('accessories.store'), $data + [
            'unit_price' => 100, 'stock_quantity' => 1, 'low_stock_threshold' => 1,
        ]);
    }

    public function test_nom_en_double_refuse_sans_tenir_compte_de_la_casse(): void
    {
        $this->product(['name' => 'Chargeur']);

        $this->createProduct(['name' => 'chargeur'])->assertSessionHasErrors('name');
        $this->createProduct(['name' => '  CHARGEUR  '])->assertSessionHasErrors('name');
        $this->assertSame(1, Accessory::count());
    }

    public function test_nom_en_double_refuse_meme_si_le_produit_est_desactive(): void
    {
        $this->product(['name' => 'Écouteurs', 'status' => 'inactif']);

        $this->createProduct(['name' => 'Écouteurs'])->assertSessionHasErrors('name');
    }

    public function test_prix_zero_refuse(): void
    {
        $this->createProduct(['name' => 'Gratuit', 'unit_price' => 0])->assertSessionHasErrors('unit_price');
        $this->assertSame(0, Accessory::count());
    }

    public function test_employe_ne_cree_pas_de_produit(): void
    {
        $this->actingAs($this->employe)->post(route('accessories.store'), [
            'name' => 'X', 'unit_price' => 10, 'stock_quantity' => 1, 'low_stock_threshold' => 1,
        ])->assertForbidden();
    }

    public function test_vente_decremente_le_stock(): void
    {
        $p = $this->product();
        $this->actingAs($this->employe)->post(route('accessories.sell'), ['accessory_id' => $p->id, 'quantity' => 3]);

        $this->assertSame(7, $p->fresh()->stock_quantity);
        $this->assertEquals(750, AccessorySale::firstOrFail()->total_amount);
    }

    public function test_vente_refusee_si_stock_insuffisant(): void
    {
        $p = $this->product(['stock_quantity' => 2]);
        $this->actingAs($this->employe)->post(route('accessories.sell'), ['accessory_id' => $p->id, 'quantity' => 3])
            ->assertSessionHas('error');

        $this->assertSame(2, $p->fresh()->stock_quantity);
        $this->assertSame(0, AccessorySale::count());
    }

    public function test_annulation_de_vente_directeur_seulement_et_restitue_le_stock(): void
    {
        $p = $this->product();
        $this->actingAs($this->employe)->post(route('accessories.sell'), ['accessory_id' => $p->id, 'quantity' => 4]);
        $sale = AccessorySale::firstOrFail();

        $this->actingAs($this->employe)->post(route('accessory-sales.cancel', $sale))->assertForbidden();
        $this->assertSame(6, $p->fresh()->stock_quantity);

        $this->actingAs($this->directeur)->post(route('accessory-sales.cancel', $sale));
        $this->assertSame(10, $p->fresh()->stock_quantity);
        $this->assertSame('annulee', $sale->fresh()->status);

        // Une seconde annulation ne restitue pas le stock une deuxième fois.
        $this->actingAs($this->directeur)->post(route('accessory-sales.cancel', $sale));
        $this->assertSame(10, $p->fresh()->stock_quantity);
    }
}
