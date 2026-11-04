<?php

namespace App\Http\Controllers;

use App\Models\Accessory;
use App\Models\AccessorySale;
use App\Models\DailyOpeningBalance;
use App\Models\Debt;
use App\Models\Transaction;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Rapports PDF (CDC §4.5) — directeur uniquement.
 * L'accès est protégé par le groupe de routes `role:directeur` (web.php),
 * pas de constructeur ni de $this->middleware() ici.
 */
class ReportController extends Controller
{
    private const SERVICES = ['moncash', 'natcash', 'zelle'];

    /** Durée maximale d'un historique de transactions (jours) : garde DomPDF léger. */
    private const MAX_HISTORY_DAYS = 92;

    // ------------------------------------------------------------------
    // Page d'accueil des rapports
    // ------------------------------------------------------------------
    public function index()
    {
        $employees = User::orderBy('name')->get(['id', 'name']);
        $debtors = Debt::select('debtor_name')->distinct()->orderBy('debtor_name')->pluck('debtor_name');

        return view('reports.index', compact('employees', 'debtors'));
    }

    // ------------------------------------------------------------------
    // 1. Rapport de clôture journalière
    // ------------------------------------------------------------------
    public function daily(Request $request)
    {
        $data = $request->validate(['date' => ['nullable', 'date']]);

        // Sans date (lien direct), on prend aujourd'hui.
        $start = Carbon::parse($data['date'] ?? today())->startOfDay();
        $end = $start->copy()->endOfDay();

        $transactions = Transaction::with('creator:id,name')
            ->whereBetween('created_at', [$start, $end])
            ->orderBy('created_at')
            ->get();

        $valid = $transactions->where('status', 'validee');

        $services = collect(self::SERVICES)->map(function ($service) use ($valid) {
            $rows = $valid->where('service', $service);

            return [
                'service'    => $service,
                'currency'   => $service === 'zelle' ? 'USD' : 'HTG',
                'count'      => $rows->count(),
                'volume'     => $rows->sum('amount'),
                'fee_client' => $rows->sum('fee_client'),
                'fee_net'    => $rows->sum('fee_net'),
            ];
        });

        $profitHtg = $valid->where('currency', 'HTG')->sum('fee_net');
        $profitUsd = $valid->where('currency', 'USD')->sum('fee_net');

        $sales = AccessorySale::valid()->with(['accessory:id,name', 'seller:id,name'])
            ->whereBetween('created_at', [$start, $end])
            ->orderBy('created_at')
            ->get();

        // Soldes (informatif) : seulement si un solde d'ouverture a été saisi.
        $openings = DailyOpeningBalance::whereDate('balance_date', $start->toDateString())
            ->get()->keyBy('service');

        $balances = [];
        foreach (['moncash', 'natcash'] as $service) {
            if (! $openings->has($service)) {
                continue;
            }
            $rows = $valid->where('service', $service);
            $in = $rows->where('movement_type', 'retrait')->sum('amount');
            $out = $rows->whereIn('movement_type', Transaction::BALANCE_DECREASE_MOVEMENTS)->sum('amount');
            $opening = (float) $openings[$service]->opening_balance;

            $balances[$service] = [
                'opening'     => $opening,
                'in'          => $in,
                'out'         => $out,
                'theoretical' => $opening + $in - $out,
            ];
        }

        return $this->pdf('reports.pdf.daily', [
            'day'          => $start,
            'transactions' => $transactions,
            'services'     => $services,
            'profitHtg'    => $profitHtg,
            'profitUsd'    => $profitUsd,
            'validCount'   => $valid->count(),
            'cancelCount'  => $transactions->where('status', 'annulee')->count(),
            'sales'        => $sales,
            'salesTotal'   => $sales->sum('total_amount'),
            'balances'     => $balances,
        ], 'cloture-' . $start->toDateString() . '.pdf');
    }

    // ------------------------------------------------------------------
    // 2. Historique des transactions (sur mesure)
    // ------------------------------------------------------------------
    public function transactions(Request $request)
    {
        $data = $request->validate([
            'from'              => ['required', 'date'],
            'to'                => ['required', 'date', 'after_or_equal:from'],
            'service'           => ['nullable', 'in:' . implode(',', self::SERVICES)],
            'employee'          => ['nullable', 'exists:users,id'],
            'include_cancelled' => ['nullable', 'boolean'],
        ], [
            'to.after_or_equal' => 'La date de fin doit être postérieure ou égale à la date de début.',
        ]);

        $start = Carbon::parse($data['from'])->startOfDay();
        $end = Carbon::parse($data['to'])->endOfDay();

        if ($start->diffInDays($end) > self::MAX_HISTORY_DAYS) {
            return back()->withInput()->with('error', 'La période ne peut pas dépasser ' . self::MAX_HISTORY_DAYS . ' jours.');
        }

        $includeCancelled = $request->boolean('include_cancelled');

        $query = Transaction::with('creator:id,name')
            ->whereBetween('created_at', [$start, $end])
            ->when($data['service'] ?? null, fn ($q, $s) => $q->where('service', $s))
            ->when($data['employee'] ?? null, fn ($q, $u) => $q->where('created_by', $u))
            ->when(! $includeCancelled, fn ($q) => $q->where('status', 'validee'))
            ->orderBy('created_at');

        $transactions = $query->get();
        $valid = $transactions->where('status', 'validee');

        $totals = $valid->groupBy('service')->map(fn ($rows) => [
            'currency'   => $rows->first()->currency,
            'count'      => $rows->count(),
            'volume'     => $rows->sum('amount'),
            'fee_client' => $rows->sum('fee_client'),
            'fee_net'    => $rows->sum('fee_net'),
        ]);

        return $this->pdf('reports.pdf.transactions', [
            'start'        => $start,
            'end'          => $end,
            'serviceLabel' => ($data['service'] ?? null) ? ucfirst($data['service']) : 'Tous les services',
            'employeeName' => ($data['employee'] ?? null) ? optional(User::find($data['employee']))->name : 'Tous les employés',
            'transactions' => $transactions,
            'totals'       => $totals,
            'profitHtg'    => $valid->where('currency', 'HTG')->sum('fee_net'),
            'profitUsd'    => $valid->where('currency', 'USD')->sum('fee_net'),
            'includeCancelled' => $includeCancelled,
            'cancelCount'  => $transactions->where('status', 'annulee')->count(),
        ], 'transactions-' . $start->toDateString() . '_' . $end->toDateString() . '.pdf');
    }

    // ------------------------------------------------------------------
    // 3. État global des dettes (bilan des créances)
    // ------------------------------------------------------------------
    public function debts()
    {
        $debts = Debt::where('status', 'active')
            ->withSum('validPayments as paid_sum', 'amount_applied')
            ->orderBy('debtor_name')
            ->orderBy('created_at')
            ->get();

        $totals = $debts->groupBy('currency')->map(fn ($rows) => [
            'count'     => $rows->count(),
            'initial'   => $rows->sum('initial_amount'),
            'paid'      => $rows->sum('paid_sum'),
            'remaining' => $rows->sum('remaining_balance'),
        ]);

        return $this->pdf('reports.pdf.debts', compact('debts', 'totals'), 'etat-dettes-' . now()->toDateString() . '.pdf');
    }

    // ------------------------------------------------------------------
    // 4. Relevé de compte client (fiche débiteur)
    // ------------------------------------------------------------------
    public function clientStatement(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'exists:debts,debtor_name'],
        ], [
            'name.required' => 'Choisissez un débiteur.',
            'name.exists'   => 'Débiteur introuvable.',
        ]);

        // Ordre d'affichage : en cours d'abord, puis soldées, abandonnées, annulées.
        $order = ['active' => 1, 'soldee' => 2, 'abandonnee' => 3, 'annulee' => 4];

        $debts = Debt::where('debtor_name', $data['name'])
            ->with(['payments' => fn ($q) => $q->orderBy('created_at'), 'payments.creator:id,name'])
            ->orderBy('created_at')
            ->get()
            ->sortBy(fn ($d) => ($order[$d->status] ?? 9) . '-' . $d->created_at->format('YmdHis'))
            ->values();

        $paidOf = fn ($d) => $d->payments->where('status', 'valide')->sum('amount_applied');

        // Situation actuelle = uniquement les dettes EN COURS, par devise.
        // Les soldées / abandonnées / annulées sont dans l'historique.
        $totals = $debts->where('status', 'active')->groupBy('currency')->map(fn ($rows) => [
            'count'     => $rows->count(),
            'initial'   => $rows->sum('initial_amount'),
            'paid'      => $rows->sum($paidOf),
            'remaining' => $rows->sum('remaining_balance'),
        ]);

        return $this->pdf('reports.pdf.client', [
            'name'   => $data['name'],
            'open'   => $debts->where('status', 'active')->values(),
            'closed' => $debts->where('status', '!=', 'active')->values(),
            'totals' => $totals,
        ], 'releve-' . \Illuminate\Support\Str::slug($data['name']) . '.pdf');
    }

    // ------------------------------------------------------------------
    // 5. Stock et ventes d'accessoires
    // ------------------------------------------------------------------
    public function stock(Request $request)
    {
        $data = $request->validate([
            'from' => ['required', 'date'],
            'to'   => ['required', 'date', 'after_or_equal:from'],
        ], [
            'to.after_or_equal' => 'La date de fin doit être postérieure ou égale à la date de début.',
        ]);

        $start = Carbon::parse($data['from'])->startOfDay();
        $end = Carbon::parse($data['to'])->endOfDay();
        // Les ventes annulées ne comptent pas dans les quantités ni les montants.
        $range = fn ($q) => $q->where('status', 'validee')->whereBetween('created_at', [$start, $end]);

        $accessories = Accessory::withSum(['sales as sold_qty' => $range], 'quantity')
            ->withSum(['sales as sold_amount' => $range], 'total_amount')
            ->orderBy('name')
            ->get();

        // Vendeur(s) par article sur la période (noms séparés par une virgule).
        $sellers = AccessorySale::valid()->with('seller:id,name')
            ->whereBetween('created_at', [$start, $end])
            ->get()
            ->groupBy('accessory_id')
            ->map(fn ($rows) => $rows->pluck('seller.name')->filter()->unique()->implode(', '));

        return $this->pdf('reports.pdf.stock', [
            'sellers'     => $sellers,
            'start'       => $start,
            'end'         => $end,
            'accessories' => $accessories,
            'stockValue'  => $accessories->where('status', 'actif')->sum(fn ($a) => $a->stock_quantity * $a->unit_price),
            'soldQty'     => $accessories->sum('sold_qty'),
            'soldAmount'  => $accessories->sum('sold_amount'),
        ], 'stock-ventes-' . $start->toDateString() . '_' . $end->toDateString() . '.pdf');
    }

    // ------------------------------------------------------------------
    // Utilitaires
    // ------------------------------------------------------------------
    private function pdf(string $view, array $data, string $filename, bool $landscape = false)
    {
        $data += [
            'logo'        => $this->logoDataUri(),
            'generatedBy' => auth()->user()->name,
            'generatedAt' => now(),
        ];

        return Pdf::loadView($view, $data)
            ->setPaper('a4', $landscape ? 'landscape' : 'portrait')
            ->stream($filename);
    }

    /** Logo embarqué en base64 (DomPDF n'a pas besoin d'accès réseau). */
    private function logoDataUri(): ?string
    {
        foreach (['images/logo_tiba.jpg', 'img/logo_tiba.jpg', 'assets/logo_tiba.jpg', 'logo_tiba.jpg'] as $relative) {
            $path = public_path($relative);
            if (is_file($path)) {
                return 'data:image/jpeg;base64,' . base64_encode(file_get_contents($path));
            }
        }

        return null;
    }
}
