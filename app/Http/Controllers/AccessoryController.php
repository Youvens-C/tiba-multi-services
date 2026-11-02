<?php

namespace App\Http\Controllers;

use App\Models\Accessory;
use App\Models\AccessorySale;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AccessoryController extends Controller
{
    // ── Page "Accessoires" : comptoir — vente rapide + ventes récentes ──────

    public function index()
    {
        $sellableAccessories = Accessory::where('status', 'actif')
            ->where('stock_quantity', '>', 0)
            ->orderBy('name')
            ->get(['id', 'name', 'unit_price', 'stock_quantity']);

        // Même règle que les transactions : l'employé ne voit que ses
        // propres ventes, le directeur voit tout.
        $recentSalesQuery = AccessorySale::with(['accessory', 'seller'])->orderBy('created_at', 'desc');
        if (auth()->user()->role !== 'directeur') {
            $recentSalesQuery->where('sold_by', auth()->id());
        }
        $recentSales = $recentSalesQuery->limit(20)->get();

        return view('accessories.index', compact('sellableAccessories', 'recentSales'));
    }

    public function sell(Request $request)
    {
        $request->validate([
            'accessory_id' => 'required|exists:accessories,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $accessory = Accessory::findOrFail($request->accessory_id);

        if (!$accessory->hasStock($request->quantity)) {
            return back()->with('error', 'Stock insuffisant pour ce produit.');
        }

        $stockBefore = $accessory->stock_quantity;

        try {
            AccessorySale::createSale(
                $accessory->id,
                $request->quantity,
                $accessory->unit_price,
                auth()->id()
            );

            AuditLog::log(
                auth()->id(),
                'vente_accessoire',
                'produit',
                $accessory->id,
                "Vente: {$request->quantity}x {$accessory->name}",
                AuditLog::diff(['stock_quantity' => $stockBefore], ['stock_quantity' => $accessory->fresh()->stock_quantity])
            );

            return redirect()->route('accessories.index')->with('success', "Vente de {$request->quantity}x {$accessory->name} enregistrée.");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function updateSale(Request $request, AccessorySale $sale)
    {
        if (!$sale->canBeModifiedBy(auth()->user())) {
            abort(403, 'Vous ne pouvez pas modifier cette vente.');
        }

        $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $oldQuantity = $sale->quantity;
        $before = $sale->only(['quantity', 'total_amount']);

        try {
            $sale->updateQuantity($request->quantity);

            AuditLog::log(
                auth()->id(),
                'modification_vente_accessoire',
                'produit',
                $sale->accessory_id,
                "Vente n°{$sale->id} du " . $sale->created_at->format('d/m/Y \à H:i') . ' corrigée',
                AuditLog::diff($before, $sale->fresh()->only(['quantity', 'total_amount']))
            );

            return redirect()->route('accessories.index')->with('success', 'Vente modifiée avec succès.');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    // ── Page "Catalogue" : gestion des produits (directeur uniquement) ──────

    public function catalog()
    {
        if (auth()->user()->role !== 'directeur') {
            abort(403, 'Accès réservé au directeur.');
        }

        $accessories = Accessory::with('creator')->orderBy('name')->get();

        return view('accessories.catalog', compact('accessories'));
    }

    public function store(Request $request)
    {
        if (auth()->user()->role !== 'directeur') {
            abort(403, 'Seul le directeur peut créer des produits.');
        }

        $request->validate([
            'name' => 'required|string|max:120',
            'description' => 'nullable|string',
            'unit_price' => 'required|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'low_stock_threshold' => 'required|integer|min:1',
        ]);

        $accessory = Accessory::create([
            'name' => $request->name,
            'description' => $request->description,
            'unit_price' => $request->unit_price,
            'stock_quantity' => $request->stock_quantity,
            'low_stock_threshold' => $request->low_stock_threshold,
            'status' => 'actif',
            'created_by' => auth()->id(),
        ]);

        AuditLog::log(auth()->id(), 'creation_produit', 'produit', $accessory->id, "Produit créé: {$accessory->name}");

        return redirect()->route('accessories.catalog')->with('success', 'Produit créé avec succès.');
    }

    public function update(Request $request, Accessory $accessory)
    {
        if (auth()->user()->role !== 'directeur') {
            abort(403, 'Seul le directeur peut modifier les produits.');
        }

        $request->validate([
            'name' => 'required|string|max:120',
            'description' => 'nullable|string',
            'unit_price' => 'required|numeric|min:0',
            'low_stock_threshold' => 'required|integer|min:1',
        ]);

        $fields = ['name', 'description', 'unit_price', 'low_stock_threshold'];
        $before = $accessory->only($fields);

        $accessory->update([
            'name' => $request->name,
            'description' => $request->description,
            'unit_price' => $request->unit_price,
            'low_stock_threshold' => $request->low_stock_threshold,
        ]);

        $changes = AuditLog::diff($before, $accessory->fresh()->only($fields));
        if ($changes) {
            AuditLog::log(
                auth()->id(),
                isset($changes['unit_price']) ? 'modification_prix' : 'modification_produit',
                'produit',
                $accessory->id,
                null,
                $changes
            );
        }

        return redirect()->route('accessories.catalog')->with('success', 'Produit modifié avec succès.');
    }

    public function toggle(Accessory $accessory)
    {
        if (auth()->user()->role !== 'directeur') {
            abort(403, 'Seul le directeur peut activer/désactiver des produits.');
        }

        $oldStatus = $accessory->status;
        $newStatus = $oldStatus === 'actif' ? 'inactif' : 'actif';
        $accessory->update(['status' => $newStatus]);

        AuditLog::log(auth()->id(), 'changement_statut_produit', 'produit', $accessory->id, null,
            AuditLog::diff(['status' => $oldStatus], ['status' => $newStatus]));

        $message = $newStatus === 'actif' ? 'Produit réactivé avec succès.' : 'Produit désactivé avec succès.';

        return redirect()->route('accessories.catalog')->with('success', $message);
    }

    public function adjustStock(Request $request, Accessory $accessory)
    {
        if (auth()->user()->role !== 'directeur') {
            abort(403, 'Seul le directeur peut ajuster le stock.');
        }

        $request->validate([
            'adjustment' => 'required|integer|min:1',
            'type' => 'required|in:add,remove',
        ]);

        $oldStock = $accessory->stock_quantity;

        if ($request->type === 'add') {
            $accessory->incrementStock($request->adjustment);
            $message = "Ajout de {$request->adjustment} unités";
        } else {
            if ($accessory->stock_quantity < $request->adjustment) {
                return back()->with('error', 'Stock insuffisant pour ce retrait.');
            }
            $accessory->decrementStock($request->adjustment);
            $message = "Retrait de {$request->adjustment} unités";
        }

        AuditLog::log(auth()->id(), 'ajustement_stock', 'produit', $accessory->id, $message,
            AuditLog::diff(['stock_quantity' => $oldStock], ['stock_quantity' => $accessory->fresh()->stock_quantity]));

        return redirect()->route('accessories.catalog')->with('success', "Stock ajusté avec succès. Nouveau stock: {$accessory->stock_quantity}");
    }
}
