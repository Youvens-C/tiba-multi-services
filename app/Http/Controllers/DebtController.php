<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Debt;
use App\Models\DebtPayment;
use Illuminate\Http\Request;

class DebtController extends Controller
{
    // Les routes sont déjà protégées par le groupe role:directeur dans web.php.

    public function index()
    {
        $activeDebts = Debt::with('payments')
            ->where('status', 'active')
            ->orderBy('created_at', 'desc')
            ->get();

        $archivedDebts = Debt::with('payments')
            ->whereIn('status', ['soldee', 'abandonnee', 'annulee'])
            ->orderBy('closed_at', 'desc')
            ->limit(100)
            ->get();

        $totalHtg = $activeDebts->where('currency', 'HTG')->sum('remaining_balance');
        $totalUsd = $activeDebts->where('currency', 'USD')->sum('remaining_balance');

        return view('debts.index', compact('activeDebts', 'archivedDebts', 'totalHtg', 'totalUsd'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'debtor_name' => 'required|string|max:150',
            'reason' => 'required|string|max:255',
            'initial_amount' => 'required|numeric|min:1',
            'currency' => 'required|in:HTG,USD',
        ]);

        $debt = Debt::create([
            'debtor_name' => $request->debtor_name,
            'reason' => $request->reason,
            'initial_amount' => $request->initial_amount,
            'currency' => $request->currency,
            'remaining_balance' => $request->initial_amount,
            'status' => 'active',
            'created_by' => auth()->id(),
        ]);

        AuditLog::log(
            auth()->id(),
            'creation_dette',
            'dette',
            $debt->id,
            "Dette créée: {$debt->debtor_name} - {$request->initial_amount} {$debt->currency}"
        );

        return redirect()->route('debts.index')->with('success', 'Dette créée avec succès.');
    }

    public function update(Request $request, Debt $debt)
    {
        if ($debt->status !== 'active') {
            return back()->with('error', "Cette dette n'est plus active.");
        }

        $request->validate([
            'debtor_name' => 'required|string|max:150',
            'reason' => 'required|string|max:255',
            'initial_amount' => 'required|numeric|min:1',
        ]);

        // Un montant initial inférieur à ce qui a déjà été payé donnerait un solde négatif.
        $totalPaid = (float) $debt->total_paid;
        if ((float) $request->initial_amount < $totalPaid) {
            return back()->with('error', 'Le montant initial ne peut pas être inférieur au total déjà payé (' . $this->fmt($totalPaid) . ' ' . $debt->currency . ').');
        }

        $fields = ['debtor_name', 'reason', 'initial_amount', 'remaining_balance'];
        $before = $debt->only($fields);
        $debt->debtor_name = $request->debtor_name;
        $debt->reason = $request->reason;
        $debt->modifyInitialAmount((float) $request->initial_amount, auth()->id());

        AuditLog::log(
            auth()->id(),
            'modification_dette',
            'dette',
            $debt->id,
            "Montants en {$debt->currency}",
            AuditLog::diff($before, $debt->fresh()->only($fields))
        );

        return redirect()->route('debts.index')->with('success', 'Dette modifiée avec succès.');
    }

    public function pay(Request $request, Debt $debt)
    {
        if ($debt->status !== 'active') {
            return back()->with('error', "Cette dette n'est plus active.");
        }

        $request->validate([
            'amount_paid' => 'required|numeric|min:0.01',
        ]);

        $before = $debt->only(['remaining_balance', 'status']);

        try {
            $payment = $debt->applyPayment((float) $request->amount_paid, auth()->id());
            $cur = $debt->currency;

            if ($debt->status === 'soldee') {
                $message = 'Dette intégralement payée !';
                if ((float) $payment->change_given > 0) {
                    $message .= ' Monnaie à rendre : ' . $this->fmt($payment->change_given) . ' ' . $cur;
                }
            } else {
                $message = "Paiement enregistré avec succès. ";
            }

            AuditLog::log(
                auth()->id(),
                'paiement_dette',
                'dette',
                $debt->id,
                "Paiement de {$request->amount_paid} {$cur} ({$debt->debtor_name})",
                AuditLog::diff($before, $debt->only(['remaining_balance', 'status']))
            );

            return redirect()->route('debts.index')->with('success', $message);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function cancelPayment($paymentId)
    {
        $payment = DebtPayment::with('debt')->findOrFail($paymentId);
        $debt = $payment->debt;

        if ($payment->status === 'annule') {
            return back()->with('error', 'Ce paiement est déjà annulé.');
        }

        // Une dette abandonnée ou annulée est close : on ne rajoute pas de solde dessus.
        if (!in_array($debt->status, ['active', 'soldee'])) {
            return back()->with('error', 'Impossible d\'annuler un paiement sur une dette abandonnée ou annulée.');
        }

        $before = $debt->only(['remaining_balance', 'status']);

        $payment->cancel(auth()->id());

        AuditLog::log(
            auth()->id(),
            'annulation_paiement',
            'paiement',
            $payment->id,
            "Paiement annulé: {$payment->amount_applied} {$debt->currency} ({$debt->debtor_name})",
            AuditLog::diff($before, $debt->fresh()->only(['remaining_balance', 'status']))
        );

        return redirect()->route('debts.index')->with('success', 'Paiement annulé, le solde de la dette a été réajusté.');
    }

    public function abandon(Debt $debt)
    {
        if ($debt->status !== 'active') {
            return back()->with('error', "Cette dette n'est plus active.");
        }

        $lostBalance = $debt->remaining_balance; // à capturer avant que abandon() ne remette le solde à 0
        $before = $debt->only(['remaining_balance', 'status']);
        $debt->abandon(auth()->id());

        AuditLog::log(
            auth()->id(),
            'abandon_dette',
            'dette',
            $debt->id,
            "Dette abandonnée: {$debt->debtor_name} - perte de {$lostBalance} {$debt->currency}",
            AuditLog::diff($before, $debt->fresh()->only(['remaining_balance', 'status']))
        );

        return redirect()->route('debts.index')->with('warning', 'Dette abandonnée. Elle a été retirée du total des dettes actives.');
    }

    public function cancel(Debt $debt)
    {
        if ($debt->status !== 'active') {
            return back()->with('error', "Cette dette n'est plus active.");
        }

        $before = $debt->only(['remaining_balance', 'status']);
        $debt->cancel(auth()->id());

        AuditLog::log(
            auth()->id(),
            'annulation_dette',
            'dette',
            $debt->id,
            "Dette annulée: {$debt->debtor_name} - {$debt->initial_amount} {$debt->currency}",
            AuditLog::diff($before, $debt->fresh()->only(['remaining_balance', 'status']))
        );

        return redirect()->route('debts.index')->with('info', 'Dette annulée. Elle a été retirée du total des dettes actives.');
    }

    private function fmt($n): string
    {
        $n = (float) $n;
        return floor($n) == $n ? number_format($n, 0, ',', ' ') : number_format($n, 2, ',', ' ');
    }
}
