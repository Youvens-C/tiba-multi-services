<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\DailyOpeningBalance;
use Illuminate\Http\Request;

class OpeningBalanceController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'opening_balance' => 'required|array',
            'opening_balance.moncash' => 'nullable|numeric|min:0',
            'opening_balance.natcash' => 'nullable|numeric|min:0',
        ]);

        $saved = [];

        foreach (['moncash', 'natcash'] as $service) {
            $value = $request->input("opening_balance.$service");
            if ($value === null || $value === '') {
                continue;
            }

            // Normalise : évite « -0 » et les décimales parasites.
            $value = round((float) $value, 2) + 0.0;

            $existing = DailyOpeningBalance::where('service', $service)
                ->whereDate('balance_date', today())
                ->first();

            if ($existing) {
                // Verrouillé après la fenêtre de 5 minutes : on ignore
                // silencieusement (le champ est normalement désactivé côté
                // interface dans ce cas, donc cette valeur ne devrait pas
                // arriver ici sauf tentative hors interface).
                if (!$existing->isEditable()) {
                    continue;
                }

                $oldValue = $existing->opening_balance;
                $existing->update(['opening_balance' => $value]);

                // Le formulaire renvoie les deux soldes : on ne journalise que ce qui a réellement changé.
                $changes = AuditLog::diff(['opening_balance' => $oldValue], ['opening_balance' => $value]);
                if ($changes) {
                    AuditLog::log(auth()->id(), 'modification_solde_ouverture', 'solde_ouverture', $existing->id, 'Montants en HTG', $changes);
                }
            } else {
                $balance = DailyOpeningBalance::create([
                    'service' => $service,
                    'balance_date' => today(),
                    'opening_balance' => $value,
                    'entered_by' => auth()->id(),
                ]);

                AuditLog::log(
                    auth()->id(),
                    'creation_solde_ouverture',
                    'solde_ouverture',
                    $balance->id,
                    'Montants en HTG',
                    AuditLog::diff(['opening_balance' => null], ['opening_balance' => $value])
                );
            }

            $saved[] = ucfirst($service);
        }

        if (empty($saved)) {
            return back()->with('error', "Aucun solde n'a été enregistré (délai de modification de 5 minutes dépassé, ou aucune valeur saisie).");
        }

        return redirect()->route('dashboard')->with('success', 'Solde d\'ouverture enregistré : ' . implode(', ', $saved) . '.');
    }
}