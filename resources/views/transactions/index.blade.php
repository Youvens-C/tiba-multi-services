@extends('layouts.app')

@section('title', 'Transactions')

@section('content')

    {{-- ── Panneau de saisie rapide ─────────────────────────────────────── --}}
    <div class="quick-entry-panel">
        <div class="quick-entry-title">
            <i class="bi bi-lightning-charge-fill" style="color:var(--green);"></i>
            Nouvelle transaction
        </div>

        <form method="POST" action="{{ route('transactions.store') }}" id="quickTxnForm">
            @csrf
            <input type="hidden" name="service" value="">
            <input type="hidden" name="movement_type" value="">

            <label class="form-label">Service</label>
            <div class="service-btn-group">
                <button type="button" class="service-btn" data-service="moncash">
                    <i class="bi bi-phone-vibrate"></i> Moncash
                </button>
                <button type="button" class="service-btn" data-service="natcash">
                    <i class="bi bi-phone"></i> Natcash
                </button>
                <button type="button" class="service-btn" data-service="zelle">
                    <i class="bi bi-cash-coin"></i> Zelle
                </button>
            </div>

            <div class="row g-3">
                <div class="col-md-4">
                    <label for="client_phone" class="form-label">Numéro du client</label>
                    <input type="tel" inputmode="numeric" name="client_phone" id="client_phone" class="form-control w-100"
                           placeholder="ex. 37123456" value="{{ old('client_phone') }}" required>
                    <div class="field-hint" id="phoneHint"></div>
                </div>

                <div class="col-md-3">
                    <label for="amount" class="form-label">Montant</label>
                    <input type="number" inputmode="decimal" step="0.01" min="1" name="amount" id="amount" class="form-control w-100"
                           placeholder="0.00" value="{{ old('amount') }}" required>
                </div>

                <div class="col-md-5">
                    <label class="form-label">Mouvement</label>
                    <div class="movement-btn-group" id="movementWrap">
                        <span class="text-muted small">Choisissez un service d'abord</span>
                    </div>
                </div>
            </div>

            <div class="row g-3 mt-1 align-items-stretch">
                <div class="col-md-8">
                    <div class="fee-summary-card" id="feeSummary">
                        <span class="text-muted">Sélectionnez un service pour commencer.</span>
                    </div>
                </div>
                <div class="col-md-4 d-flex">
                    <button type="submit" class="btn-tiba-primary w-100" id="txnSubmitBtn" disabled>
                        <i class="bi bi-check-circle me-1"></i> Enregistrer
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- ── Filtres ───────────────────────────────────────────────────────── --}}
    <div class="filter-bar">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-6 col-md-2">
                <label class="form-label mb-1">Service</label>
                <select name="service" class="form-select">
                    <option value="">Tous</option>
                    <option value="moncash" @selected(($filters['service'] ?? '') === 'moncash')>Moncash</option>
                    <option value="natcash" @selected(($filters['service'] ?? '') === 'natcash')>Natcash</option>
                    <option value="zelle" @selected(($filters['service'] ?? '') === 'zelle')>Zelle</option>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label mb-1">Statut</label>
                <select name="status" class="form-select">
                    <option value="">Tous</option>
                    <option value="validee" @selected(($filters['status'] ?? '') === 'validee')>Validée</option>
                    <option value="annulee" @selected(($filters['status'] ?? '') === 'annulee')>Annulée</option>
                </select>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label mb-1">Du</label>
                <input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] ?? '' }}">
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label mb-1">Au</label>
                <input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] ?? '' }}">
            </div>
            <div class="col-12 col-md-2 d-flex gap-2">
                <button type="submit" class="btn-tiba-secondary flex-fill">Filtrer</button>
                <a href="{{ route('transactions.index') }}" class="btn-tiba-secondary flex-fill text-center">Réinitialiser</a>
            </div>
        </form>
    </div>

    {{-- ── Liste ─────────────────────────────────────────────────────────── --}}
    <div class="panel">
        @if($transactions->isEmpty())
            <p class="text-muted mb-0">Aucune transaction ne correspond à ces critères.</p>
        @else
            <div class="table-responsive">
                <table class="table table-tiba align-middle">
                    <thead>
                        <tr>
                            <th>Heure</th>
                            <th>Service</th>
                            <th>Mouvement</th>
                            <th>Client</th>
                            <th>Montant</th>
                            <th>Frais</th>
                            <th>Statut</th>
                            <th>Par</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody> 
                        @foreach($transactions as $t)
                            <tr>
                                <td class="text-muted">{{ $t->created_at->format('H:i') }}</td>
                                <td><span class="badge-service">{{ $t->service }}</span></td>
                                <td>{{ $t->movement_label }}</td>
                                <td>{{ $t->client_phone }}</td>
                                <td class="fw-semibold">{{ number_format($t->amount, 2) }} {{ $t->currency }}</td>
                                <td>{{ number_format($t->fee_client, 2) }} {{ $t->currency }}</td>
                                <td>
                                    <span class="status-{{ $t->status === 'annulee' ? 'annulee' : 'validee' }}">
                                        {{ $t->status === 'annulee' ? 'Annulée' : 'Validée' }}
                                    </span>
                                </td>
                                <td class="text-muted">{{ $t->creator->name ?? '—' }}</td>
                                <td class="text-end">
                                    <div class="d-flex gap-2 justify-content-end">
                                        @if($t->canBeModifiedBy(auth()->user()))
                                            <button type="button" class="btn-tiba-secondary js-edit-transaction" style="padding:0.3rem 0.7rem;font-size:0.8rem;"
                                                    data-url="{{ route('transactions.update', $t) }}"
                                                    data-service="{{ $t->service }}"
                                                    data-movement="{{ $t->movement_type }}"
                                                    data-phone="{{ $t->client_phone }}"
                                                    data-amount="{{ $t->amount }}">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                        @endif
                                        @if(auth()->user()->role === 'directeur' && $t->status !== 'annulee')
                                            <form method="POST" action="{{ route('transactions.cancel', $t) }}" class="js-confirm-cancel">
                                                @csrf
                                                <button type="submit" class="btn-tiba-danger" style="padding:0.3rem 0.7rem;font-size:0.8rem;">
                                                    <i class="bi bi-x-circle"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $transactions->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>

    <div class="modal fade" id="editTransactionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius:16px; border:none;">
                <form method="POST" id="editTxnForm">
                    @csrf
                    @method('PUT')
                    <div class="modal-body p-4">
                        <h5 class="mb-2">Corriger la transaction</h5>
                        <div class="d-flex gap-2 mb-3">
                            <span class="badge-service" id="editServiceBadge"></span>
                            <span class="badge-service" id="editMovementBadge"></span>
                            <span class="text-muted small align-self-center">Non modifiables.</span>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Numéro du client</label>
                            <input type="tel" inputmode="numeric" name="client_phone" class="form-control w-100" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Montant</label>
                            <input type="number" inputmode="decimal" step="0.01" min="1" name="amount" class="form-control w-100" required>
                        </div>
                        <div class="fee-summary-card" id="editFeeSummary"></div>
                    </div>
                    <div class="modal-footer border-0 px-4 pb-4 pt-0">
                        <button type="button" class="btn-tiba-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn-tiba-primary" id="editTxnSubmitBtn">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script src="{{ asset('js/transactions.js') }}"></script>
    <script>
        initTransactionForm({
            formId: 'quickTxnForm',
            serviceFees: @json($serviceFees),
            operatorPrefixes: @json($operatorPrefixes),
        });
        initEditTransactionModal({ serviceFees: @json($serviceFees) });

        document.querySelectorAll('.js-confirm-cancel').forEach(form => {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                confirmAction(form, {
                    title: 'Annuler la transaction ?',
                    message: "Cette action est irréversible.",
                    confirmLabel: 'Oui, annuler',
                });
            });
        });
    </script>
@endpush
