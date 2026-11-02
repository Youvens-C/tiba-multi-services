@extends('layouts.app')

@section('title', 'Tableau de bord')

@section('content')

    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-speedometer2 fs-4" style="color:var(--navy);"></i>
            <div>
                <h5 class="mb-0">Bonjour, {{ auth()->user()->name }}</h5>
                <small class="text-muted">{{ now()->translatedFormat('l j F Y') }}</small>
            </div>
        </div>
        @if($isAdmin)
            <button type="button" class="btn-tiba-secondary" data-bs-toggle="modal" data-bs-target="#openingBalanceModal">
                <i class="bi bi-wallet2 me-1"></i> Solde d'ouverture
            </button>
        @endif
    </div>

    @if($isAdmin)
        @if(isset($theoreticalBalances['moncash']) || isset($theoreticalBalances['natcash']))
            <div class="panel mb-4">
                <div class="panel-title">
                    Solde théorique du téléphone
                </div>
                <div class="row g-3">
                    @foreach(['moncash' => 'Moncash', 'natcash' => 'Natcash'] as $key => $label)
                        @if(isset($theoreticalBalances[$key]))
                            <div class="col-sm-6">
                                <div class="d-flex justify-content-between align-items-center p-3" style="background:var(--bg); border-radius:var(--radius);">
                                    <div>
                                        <div class="text-muted small">{{ $label }}</div>
                                        <div class="fw-bold" style="font-family:var(--font-heading); font-size:1.3rem;">{{ number_format($theoreticalBalances[$key], 2) }} HTG</div>
                                    </div>
                                    <div class="text-end text-muted" style="font-size:0.78rem;">
                                        Ouverture<br>{{ number_format($openingBalances[$key]->opening_balance, 2) }} HTG
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        @endif
        <div class="row g-3 mb-3">
            <div class="col-sm-6 col-xl-3">
                <div class="metric-card">
                    <div class="metric-icon bg-navy"><i class="bi bi-phone-vibrate"></i></div>
                    <div>
                        <div class="metric-value">{{ number_format($todayProfitMoncash, 2) }}</div>
                        <div class="metric-label">Profit Moncash (HTG)</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="metric-card">
                    <div class="metric-icon bg-navy"><i class="bi bi-phone"></i></div>
                    <div>
                        <div class="metric-value">{{ number_format($todayProfitNatcash, 2) }}</div>
                        <div class="metric-label">Profit Natcash (HTG)</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="metric-card">
                    <div class="metric-icon bg-green"><i class="bi bi-cash-coin"></i></div>
                    <div>
                        <div class="metric-value">{{ number_format($todayProfitUsd, 2) }}</div>
                        <div class="metric-label">Profit Zelle (USD)</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="metric-card">
                    <div class="metric-icon bg-warning"><i class="bi bi-cash-coin"></i></div>
                    <div>
                        <div class="metric-value">{{ $activeDebts }}</div>
                        <div class="metric-label">
                            Dettes actives<br>
                            {{ number_format($activeDebtsTotal, 2) }} HTG / {{ number_format($activeDebtsTotalUsd, 2) }} USD
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="metric-card">
                <div class="metric-icon bg-navy"><i class="bi bi-arrow-left-right"></i></div>
                <div>
                    <div class="metric-value">{{ $todayTransactionsCount }}</div>
                    <div class="metric-label">Transactions du jour</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="metric-card">
                <div class="metric-icon bg-green"><i class="bi bi-phone-fill"></i></div>
                <div>
                    <div class="metric-value">{{ $todayAccessoryCount }}</div>
                    <div class="metric-label"> Ventes accessoires
                    @if($isAdmin)
                        ({{ number_format($todayAccessoryRevenue, 2) }} HTG)
                    @endif
                </div>
                </div>
            </div>
        </div>
    </div>

    @if($lowStockItems->isNotEmpty())
        <div class="alert-tiba-warning mb-4 d-flex align-items-center gap-2">
            <i class="bi bi-exclamation-triangle"></i>
            <span>
                {{ $lowStockItems->count() }} produit(s) en stock bas :
                {{ $lowStockItems->pluck('name')->join(', ') }}
            </span>
        </div>
    @endif

    <div class="panel">
        <div class="panel-title">Les 7 dernières transactions</div>

        @if($recentTransactions->isEmpty())
            <p class="text-muted mb-0">Aucune transaction enregistrée pour le moment.</p>
        @else
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr style="font-size:0.8rem; color:var(--text-muted);">
                            <th>Service</th>
                            <th>Mouvement</th>
                            <th>Client</th>
                            <th>Montant</th>
                            <th>Frais</th>
                            <th>Statut</th>
                            <th>Par</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentTransactions as $t)
                            <tr>
                                <td class="text-capitalize">{{ $t->service }}</td>
                                <td>{{ $t->movement_label }}</td>
                                <td>{{ $t->client_phone }}</td>
                                <td>{{ number_format($t->amount, 2) }} {{ $t->currency }}</td>
                                <td>{{ number_format($t->fee_client, 2) }} {{ $t->currency }}</td>
                                <td>
                                    @if($t->status === 'annulee')
                                        <span class="status-annulee">Annulée</span>
                                    @else
                                        <span class="status-validee">Validée</span>
                                    @endif
                                </td>
                                <td>{{ $t->creator->name ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    @if($isAdmin)
        <div class="modal fade" id="openingBalanceModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content" style="border-radius:16px; border:none;">
                    <form method="POST" action="{{ route('admin.opening-balances.store') }}">
                        @csrf
                        <div class="modal-body p-4">
                            <h5 class="mb-1">Solde d'ouverture du jour</h5>
                            <p class="text-muted small mb-3">Une saisie par service.</p>

                            @foreach(['moncash' => 'Moncash', 'natcash' => 'Natcash'] as $key => $label)
                                <div class="mb-3">
                                    <label class="form-label">{{ $label }}</label>
                                    @if($canEditOpening[$key])
                                        <input type="number" step="0.01" min="0" inputmode="decimal"
                                               name="opening_balance[{{ $key }}]" class="form-control w-100"
                                               value="{{ old('opening_balance.'.$key, $openingBalances[$key]->opening_balance ?? '') }}"
                                               placeholder="0.00">
                                    @else
                                        <input type="text" class="form-control w-100"
                                               value="{{ number_format($openingBalances[$key]->opening_balance, 2) }} HTG verrouillé" disabled>
                                        <div class="field-hint">Saisi il y a plus de 5 minutes.</div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                        <div class="modal-footer border-0 px-4 pb-4 pt-0">
                            <button type="button" class="btn-tiba-secondary" data-bs-dismiss="modal">Fermer</button>
                            <button type="submit" class="btn-tiba-primary">Enregistrer</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

@endsection

@push('scripts')
@if($isAdmin)
<script>
(function () {
    const lockTimestamps = {
        moncash: @json(optional($openingBalances['moncash'] ?? null)->created_at?->timestamp),
        natcash: @json(optional($openingBalances['natcash'] ?? null)->created_at?->timestamp),
    };
    const EDIT_WINDOW_SECONDS = 5 * 60; // à garder synchronisé avec DailyOpeningBalance::EDIT_WINDOW_MINUTES

    Object.entries(lockTimestamps).forEach(([service, createdAtTs]) => {
        if (!createdAtTs) return; // rien saisi aujourd'hui pour ce service

        const remainingMs = (createdAtTs + EDIT_WINDOW_SECONDS) * 1000 - Date.now();
        if (remainingMs <= 0) return; // déjà verrouillé par le serveur au chargement

        setTimeout(() => {
            const input = document.querySelector(`[name="opening_balance[${service}]"]`);
            if (!input || input.disabled) return;

            const value = input.value;
            input.disabled = true;
            input.value = value ? `${parseFloat(value).toLocaleString('fr-FR', {minimumFractionDigits:2})} HTG — verrouillé` : '';

            const hint = document.createElement('div');
            hint.className = 'field-hint';
            hint.textContent = "Le délai de 5 minutes vient d'expirer pour ce service.";
            input.insertAdjacentElement('afterend', hint);
        }, remainingMs);
    });
})();
</script>
@endif
@endpush
