@extends('layouts.app')

@section('title', 'Dettes')

@push('styles')
<style>
    .debt-tabs { display: flex; gap: .25rem; border-bottom: 1px solid var(--border); margin-bottom: 1rem; }
    .debt-tab { border: none; background: none; padding: .6rem 1rem; font-weight: 600; font-size: .9rem;
                color: var(--text-muted); border-bottom: 3px solid transparent; margin-bottom: -1px; }
    .debt-tab.active { color: var(--navy); border-bottom-color: var(--green); }
    .debt-pane { display: none; }
    .debt-pane.active { display: block; }
    .debt-actions { display: flex; gap: .4rem; justify-content: flex-end; flex-wrap: nowrap; }
</style>
@endpush

@section('content')

    <div class="row g-3 mb-4 align-items-stretch">
        <div class="col-sm-6 col-lg-4">
            <div class="metric-card">
                <div class="metric-icon bg-warning"><i class="bi bi-cash-coin"></i></div>
                <div>
                    <div class="metric-value">{{ number_format($totalHtg, 2) }}</div>
                    <div class="metric-label">Total des dettes actives (HTG)</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-4">
            <div class="metric-card">
                <div class="metric-icon bg-warning"><i class="bi bi-currency-dollar"></i></div>
                <div>
                    <div class="metric-value">{{ number_format($totalUsd, 2) }}</div>
                    <div class="metric-label">Total des dettes actives (USD)</div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 d-flex align-items-center justify-content-lg-end">
            <button type="button" class="btn-tiba-primary" data-bs-toggle="modal" data-bs-target="#createDebtModal">
                <i class="bi bi-plus-lg me-1"></i> Nouvelle dette
            </button>
        </div>
    </div>

    <div class="panel">
        <div class="debt-tabs">
            <button type="button" class="debt-tab active" data-pane="paneActive">Actives ({{ $activeDebts->count() }})</button>
            <button type="button" class="debt-tab" data-pane="paneArchive">Archives / Historique ({{ $archivedDebts->count() }})</button>
        </div>

        {{-- ── Dettes actives ───────────────────────────────────────────── --}}
        <div class="debt-pane active" id="paneActive">
            @if($activeDebts->isEmpty())
                <p class="text-muted mb-0">Aucune dette active.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-tiba align-middle">
                        <thead>
                            <tr>
                                <th>Débiteur</th>
                                <th>Motif</th>
                                <th>Montant initial</th>
                                <th>Payé</th>
                                <th>Solde restant</th>
                                <th>Date</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($activeDebts as $debt)
                                @php
                                    $paid = (float) $debt->initial_amount - (float) $debt->remaining_balance;
                                    $paymentsData = $debt->payments->sortByDesc('created_at')->map(fn($p) => [
                                        'id' => $p->id,
                                        'date' => $p->created_at->format('d/m/Y H:i'),
                                        'paid' => (float) $p->amount_paid,
                                        'applied' => (float) $p->amount_applied,
                                        'change' => (float) $p->change_given,
                                        'status' => $p->status,
                                    ])->values();
                                @endphp
                                <tr>
                                    <td class="fw-semibold">{{ $debt->debtor_name }}</td>
                                    <td class="text-muted">{{ $debt->reason }}</td>
                                    <td>{{ number_format($debt->initial_amount, 2) }} {{ $debt->currency }}</td>
                                    <td>{{ number_format($paid, 2) }} {{ $debt->currency }}</td>
                                    <td class="fw-semibold">{{ number_format($debt->remaining_balance, 2) }} {{ $debt->currency }}</td>
                                    <td class="text-muted">{{ $debt->created_at->format('d/m/Y H:i') }}</td>
                                    <td>
                                        <div class="debt-actions">
                                            <button type="button" class="btn-tiba-primary js-pay-debt" style="padding:.3rem .8rem;font-size:.8rem;"
                                                    data-url="{{ route('debts.pay', $debt) }}"
                                                    data-name="{{ $debt->debtor_name }}"
                                                    data-balance="{{ $debt->remaining_balance }}"
                                                    data-currency="{{ $debt->currency }}">
                                                <i class="bi bi-cash me-1"></i> Payer
                                            </button>
                                            <button type="button" class="btn-tiba-secondary js-history" title="Historique des paiements"
                                                    style="padding:.3rem .7rem;font-size:.8rem;"
                                                    data-name="{{ $debt->debtor_name }}" data-currency="{{ $debt->currency }}"
                                                    data-status="{{ $debt->status }}"
                                                    data-payments="{{ json_encode($paymentsData) }}">
                                                <i class="bi bi-clock-history"></i>
                                            </button>
                                            <button type="button" class="btn-tiba-secondary js-edit-debt" title="Modifier"
                                                    style="padding:.3rem .7rem;font-size:.8rem;"
                                                    data-url="{{ route('debts.update', $debt) }}"
                                                    data-name="{{ $debt->debtor_name }}" data-reason="{{ $debt->reason }}"
                                                    data-amount="{{ $debt->initial_amount }}" data-paid="{{ $paid }}"
                                                    data-currency="{{ $debt->currency }}">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <form method="POST" action="{{ route('debts.abandon', $debt) }}" class="js-debt-confirm"
                                                  data-kind="abandon" data-name="{{ $debt->debtor_name }}">
                                                @csrf
                                                <button type="submit" class="btn-tiba-secondary" title="Abandonner (perte)"
                                                        style="padding:.3rem .7rem;font-size:.8rem;">
                                                    <i class="bi bi-flag"></i>
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route('debts.cancel', $debt) }}" class="js-debt-confirm"
                                                  data-kind="cancel" data-name="{{ $debt->debtor_name }}">
                                                @csrf
                                                <button type="submit" class="btn-tiba-danger" title="Annuler (erreur de création)"
                                                        style="padding:.3rem .7rem;font-size:.8rem;">
                                                    <i class="bi bi-x-circle"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{-- ── Archives ─────────────────────────────────────────────────── --}}
        <div class="debt-pane" id="paneArchive">
            @if($archivedDebts->isEmpty())
                <p class="text-muted mb-0">Aucune dette archivée.</p>
            @else
                <div class="movement-btn-group mb-3" id="archiveFilters">
                    <button type="button" class="movement-btn active" data-filter="all">Toutes</button>
                    <button type="button" class="movement-btn" data-filter="soldee">Soldées</button>
                    <button type="button" class="movement-btn" data-filter="abandonnee">Abandonnées</button>
                    <button type="button" class="movement-btn" data-filter="annulee">Annulées</button>
                </div>

                @php
                    $statusMap = [
                        'soldee' => ['Soldée', 'status-validee'],
                        'abandonnee' => ['Abandonnée', 'status-annulee'],
                        'annulee' => ['Annulée', 'status-inactif'],
                    ];
                @endphp

                <div class="table-responsive">
                    <table class="table table-tiba align-middle">
                        <thead>
                            <tr>
                                <th>Débiteur</th>
                                <th>Motif</th>
                                <th>Montant initial</th>
                                <th>Total payé</th>
                                <th>Perte</th>
                                <th>Statut</th>
                                <th>Clôturée le</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($archivedDebts as $debt)
                                @php
                                    $paidTotal = (float) $debt->payments->where('status', 'valide')->sum('amount_applied');
                                    $lost = $debt->status === 'abandonnee' ? (float) $debt->initial_amount - $paidTotal : null;
                                    $paymentsData = $debt->payments->sortByDesc('created_at')->map(fn($p) => [
                                        'id' => $p->id,
                                        'date' => $p->created_at->format('d/m/Y H:i'),
                                        'paid' => (float) $p->amount_paid,
                                        'applied' => (float) $p->amount_applied,
                                        'change' => (float) $p->change_given,
                                        'status' => $p->status,
                                    ])->values();
                                @endphp
                                <tr class="js-archive-row" data-status="{{ $debt->status }}">
                                    <td class="fw-semibold">{{ $debt->debtor_name }}</td>
                                    <td class="text-muted">{{ $debt->reason }}</td>
                                    <td>{{ number_format($debt->initial_amount, 2) }} {{ $debt->currency }}</td>
                                    <td>{{ number_format($paidTotal, 2) }} {{ $debt->currency }}</td>
                                    <td>{{ $lost !== null ? number_format($lost, 2) . ' ' . $debt->currency : '—' }}</td>
                                    <td><span class="{{ $statusMap[$debt->status][1] ?? '' }}">{{ $statusMap[$debt->status][0] ?? $debt->status }}</span></td>
                                    <td class="text-muted">{{ $debt->closed_at ? $debt->closed_at->format('d/m/Y H:i') : '—' }}</td>
                                    <td class="text-end">
                                        <button type="button" class="btn-tiba-secondary js-history" title="Historique des paiements"
                                                style="padding:.3rem .7rem;font-size:.8rem;"
                                                data-name="{{ $debt->debtor_name }}" data-currency="{{ $debt->currency }}"
                                                data-status="{{ $debt->status }}"
                                                data-payments="{{ json_encode($paymentsData) }}">
                                            <i class="bi bi-clock-history"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    {{-- ── Modale : nouvelle dette ───────────────────────────────────────── --}}
    <div class="modal fade" id="createDebtModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius:16px; border:none;">
                <form method="POST" action="{{ route('debts.store') }}">
                    @csrf
                    <div class="modal-body p-4">
                        <h5 class="mb-3">Nouvelle dette</h5>
                        <div class="mb-3">
                            <label class="form-label">Nom du débiteur</label>
                            <input type="text" name="debtor_name" class="form-control w-100" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Motif</label>
                            <input type="text" name="reason" class="form-control w-100" placeholder="ex. prêt d'argent, minutes Digicel" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Devise</label>
                            <div class="movement-btn-group" id="createCurrencyGroup">
                                <button type="button" class="movement-btn active" data-currency="HTG">HTG</button>
                                <button type="button" class="movement-btn" data-currency="USD">USD</button>
                            </div>
                            <input type="hidden" name="currency" id="createCurrency" value="HTG">
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Montant</label>
                            <input type="number" step="0.01" min="1" inputmode="decimal" name="initial_amount" class="form-control w-100" required>
                        </div>
                    </div>
                    <div class="modal-footer border-0 px-4 pb-4 pt-0">
                        <button type="button" class="btn-tiba-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn-tiba-primary">Créer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ── Modale : modifier une dette ───────────────────────────────────── --}}
    <div class="modal fade" id="editDebtModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius:16px; border:none;">
                <form method="POST" id="editDebtForm">
                    @csrf
                    @method('PUT')
                    <div class="modal-body p-4">
                        <h5 class="mb-3">Modifier la dette</h5>
                        <div class="mb-3">
                            <label class="form-label">Nom du débiteur</label>
                            <input type="text" name="debtor_name" id="editDebtName" class="form-control w-100" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Motif</label>
                            <input type="text" name="reason" id="editDebtReason" class="form-control w-100" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Montant initial (<span id="editDebtCurrency"></span>)</label>
                            <input type="number" step="0.01" min="1" inputmode="decimal" name="initial_amount" id="editDebtAmount" class="form-control w-100" required>
                            <div class="field-hint" id="editDebtHint"></div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 px-4 pb-4 pt-0">
                        <button type="button" class="btn-tiba-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn-tiba-primary" id="editDebtSubmit">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ── Modale : enregistrer un paiement ──────────────────────────────── --}}
    <div class="modal fade" id="payDebtModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius:16px; border:none;">
                <form method="POST" id="payDebtForm">
                    @csrf
                    <div class="modal-body p-4">
                        <h5 class="mb-1">Enregistrer un paiement</h5>
                        <p class="text-muted small mb-3" id="payDebtInfo"></p>
                        <label class="form-label">Montant remis par le débiteur</label>
                        <input type="number" step="0.01" min="0.01" inputmode="decimal" name="amount_paid" id="payAmount" class="form-control w-100" required>
                        <div class="fee-summary-card mt-3" id="payPreview"></div>
                    </div>
                    <div class="modal-footer border-0 px-4 pb-4 pt-0">
                        <button type="button" class="btn-tiba-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn-tiba-primary" id="paySubmit" disabled>Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ── Modale : historique des paiements ─────────────────────────────── --}}
    <div class="modal fade" id="historyModal" tabindex="-1" aria-hidden="true"
         data-cancel-url="{{ route('debts.cancel-payment', ['id' => '__ID__']) }}">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content" style="border-radius:16px; border:none;">
                <div class="modal-body p-4">
                    <h5 class="mb-1">Historique des paiements</h5>
                    <p class="text-muted small mb-3" id="historyTitle"></p>
                    <div id="historyBody"></div>
                </div>
                <div class="modal-footer border-0 px-4 pb-4 pt-0">
                    <button type="button" class="btn-tiba-secondary" data-bs-dismiss="modal">Fermer</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script src="{{ asset('js/debts.js') }}"></script>
@endpush
