<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\ServiceFee;
use App\Models\OperatorPrefix;
use App\Models\AuditLog;
use App\Support\PhoneNumber;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TransactionController extends Controller
{
    private const MAX_AMOUNT = [
        'moncash' => 74999,
        'natcash' => 99999,
    ];

    // Nouveaux mouvements demandés par le directeur : aucun frais, montant
    // minimum propre à chacun, pas de plafond maximum. Diminuent tous le
    // solde mobile de l'agent (même sens qu'un Transfert/Dépôt classique).
    private const NO_FEE_MOVEMENTS = [
        'pap_padap', 'lapoula', 'depot_paryaj_pam', 'depot_paryaj_lakay', 'depot_lotto_mobile',
    ];

    private const NO_FEE_MIN_AMOUNTS = [
        'pap_padap' => 10,
        'lapoula' => 5,
        'depot_paryaj_pam' => 50,
        'depot_paryaj_lakay' => 50,
        'depot_lotto_mobile' => 50,
    ];

    public function index(Request $request)
    {
        $query = Transaction::with('creator');

        if ($request->service) {
            $query->where('service', $request->service);
        }
        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->date_from && $request->date_to) {
            $query->whereBetween('created_at', [
                $request->date_from . ' 00:00:00',
                $request->date_to . ' 23:59:59',
            ]);
        }

        // Employé : uniquement ses propres transactions (tableau de permissions du CDC)
        if (auth()->user()->role === 'employe') {
            $query->where('created_by', auth()->id());
        }

        $transactions = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        return view('transactions.index', [
            'transactions' => $transactions,
            'serviceFees' => ServiceFee::where('status', 'actif')->get(['service', 'movement_type', 'min_amount', 'max_amount', 'fee']),
            'operatorPrefixes' => OperatorPrefix::where('status', 'actif')->get(['operator', 'service', 'prefix']),
            'filters' => $request->only(['service', 'status', 'date_from', 'date_to']),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'client_phone' => 'required|string|max:20',
            'amount' => 'required|numeric|min:1',
            'service' => ['required', Rule::in(['moncash', 'natcash', 'zelle'])],
            'movement_type' => [
                'required',
                Rule::in(['retrait', 'transfert', 'depot', 'pap_padap', 'lapoula', 'depot_paryaj_pam', 'depot_paryaj_lakay', 'depot_lotto_mobile']),
                function ($attribute, $value, $fail) use ($request) {
                    $allowed = [
                        'moncash' => ['retrait', 'transfert', 'pap_padap', 'depot_paryaj_pam', 'depot_paryaj_lakay', 'depot_lotto_mobile'],
                        'natcash' => ['retrait', 'transfert', 'depot', 'lapoula', 'depot_paryaj_pam', 'depot_paryaj_lakay', 'depot_lotto_mobile'],
                        'zelle'   => ['transfert'],
                    ];
                    if (!in_array($value, $allowed[$request->service] ?? [])) {
                        $fail("Le mouvement « {$value} » n'est pas valide pour {$request->service}.");
                    }
                },
            ],
        ]);

        $user = auth()->user();
        $currency = $request->service === 'zelle' ? 'USD' : 'HTG';

        // Format, longueur et cohérence avec le service (8 chiffres = Moncash/Natcash,
        // 10 chiffres = Zelle). Le numéro est enregistré sans indicatif ni séparateurs.
        $phone = PhoneNumber::forService($request->client_phone, $request->service);
        if (!$phone['ok']) {
            return back()->withInput()->with('error', $phone['error']);
        }
        $phoneCore = $phone['core'];

        // Le contrôle de préfixe ne s'applique pas à Zelle (RG20 = Moncash/Natcash uniquement,
        // Zelle n'utilise pas les préfixes opérateurs haïtiens).
        if ($request->service !== 'zelle') {
            $prefixData = OperatorPrefix::detectService($phoneCore);
            if (!$prefixData) {
                return back()->withInput()->with('error', 'Numéro de téléphone invalide ou préfixe non reconnu.');
            }
            if ($prefixData['service'] !== $request->service) {
                return back()->withInput()->with('error', "Le numéro saisi est un préfixe {$prefixData['service']}, pas {$request->service}.");
            }

            if (in_array($request->movement_type, self::NO_FEE_MOVEMENTS)) {
                $minAmount = self::NO_FEE_MIN_AMOUNTS[$request->movement_type];
                if ($request->amount < $minAmount) {
                    return back()->withInput()->with('error', "Le montant minimum pour cette opération est de " . number_format($minAmount, 0) . " HTG.");
                }
            } else {
                $maxAmount = self::MAX_AMOUNT[$request->service];
                if ($request->amount > $maxAmount) {
                    return back()->withInput()->with('error', "Le montant maximum pour {$request->service} est de " . number_format($maxAmount, 0) . " HTG.");
                }
            }
        }

        [$feeClient, $feeNet] = $this->calculateFees($request->service, $request->movement_type, (float) $request->amount);
        if ($feeClient === null) {
            return back()->withInput()->with('error', 'Aucun tarif trouvé pour ce montant.');
        }

        $transaction = Transaction::create([
            'service' => $request->service,
            'movement_type' => $request->movement_type,
            'client_phone' => $phoneCore,
            'amount' => $request->amount,
            'fee_client' => $feeClient,
            'fee_net' => $feeNet,
            'currency' => $currency,
            'status' => 'validee',
            'created_by' => $user->id,
        ]);

        AuditLog::log(
            $user->id,
            'creation_transaction',
            'transaction',
            $transaction->id,
            "Transaction {$request->service} - {$request->movement_type} - {$request->amount} {$currency}"
        );

        return redirect()->route('transactions.index')->with('success', 'Transaction enregistrée avec succès !');
    }

    public function update(Request $request, Transaction $transaction)
    {
        if (!$transaction->canBeModifiedBy(auth()->user())) {
            abort(403, 'Vous ne pouvez pas modifier cette transaction.');
        }

        $request->validate([
            'client_phone' => 'required|string|max:20',
            'amount' => 'required|numeric|min:1',
        ]);

        $phone = PhoneNumber::forService($request->client_phone, $transaction->service);
        if (!$phone['ok']) {
            return back()->withInput()->with('error', $phone['error']);
        }
        $phoneCore = $phone['core'];

        if ($transaction->service !== 'zelle') {
            $prefixData = OperatorPrefix::detectService($phoneCore);
            if (!$prefixData || $prefixData['service'] !== $transaction->service) {
                return back()->withInput()->with('error', 'Le numéro saisi ne correspond pas au service de cette transaction.');
            }

            if (in_array($transaction->movement_type, self::NO_FEE_MOVEMENTS)) {
                $minAmount = self::NO_FEE_MIN_AMOUNTS[$transaction->movement_type];
                if ($request->amount < $minAmount) {
                    return back()->withInput()->with('error', "Le montant minimum pour cette opération est de " . number_format($minAmount, 0) . " HTG.");
                }
            } else {
                $maxAmount = self::MAX_AMOUNT[$transaction->service];
                if ($request->amount > $maxAmount) {
                    return back()->withInput()->with('error', "Montant maximum pour {$transaction->service} : " . number_format($maxAmount, 0) . " HTG.");
                }
            }
        }

        [$feeClient, $feeNet] = $this->calculateFees($transaction->service, $transaction->movement_type, (float) $request->amount);
        if ($feeClient === null) {
            return back()->withInput()->with('error', 'Aucun tarif trouvé pour ce montant.');
        }

        $fields = ['client_phone', 'amount', 'fee_client', 'fee_net'];
        $before = $transaction->only($fields);

        $transaction->client_phone = $phoneCore;
        $transaction->amount = $request->amount;
        $transaction->fee_client = $feeClient;
        $transaction->fee_net = $feeNet;
        $transaction->last_updated_by = auth()->id();
        $transaction->last_updated_at = now();
        $transaction->save();

        AuditLog::log(
            auth()->id(),
            'modification_transaction',
            'transaction',
            $transaction->id,
            "Montants en {$transaction->currency}",
            AuditLog::diff($before, $transaction->fresh()->only($fields))
        );

        return redirect()->route('transactions.index')->with('success', 'Transaction modifiée avec succès.');
    }

    public function cancel(Request $request, Transaction $transaction)
    {
        if (auth()->user()->role !== 'directeur') {
            abort(403, 'Seul le directeur peut annuler une transaction.');
        }
        if ($transaction->status === 'annulee') {
            return back()->with('error', 'Cette transaction est déjà annulée.');
        }

        $oldStatus = $transaction->status;
        $transaction->cancel(auth()->user());

        AuditLog::log(
            auth()->id(),
            'annulation_transaction',
            'transaction',
            $transaction->id,
            "Montant annulé : {$transaction->amount} {$transaction->currency}",
            AuditLog::diff(['status' => $oldStatus], ['status' => $transaction->status])
        );

        return redirect()->route('transactions.index')->with('success', 'Transaction annulée avec succès.');
    }

    private function calculateFees(string $service, string $movementType, float $amount): array
    {
        if (in_array($movementType, self::NO_FEE_MOVEMENTS)) {
            return [0, 0];
        }

        if ($service === 'zelle') {
            $feeClient = round($amount * 0.10, 2);
            return [$feeClient, round($feeClient / 2, 2)];
        }

        $feeClient = ServiceFee::findFee($service, $movementType, $amount);
        if ($feeClient === null) {
            return [null, null];
        }

        $feeNet = ($service === 'natcash' && $movementType === 'transfert') ? 0 : $feeClient;
        return [$feeClient, $feeNet];
    }
}
