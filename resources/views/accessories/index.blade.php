@extends('layouts.app')

@section('title', 'Accessoires')

@section('content')

    <div class="quick-entry-panel">
        <div class="quick-entry-title">
            <i class="bi bi-lightning-charge-fill" style="color:var(--green);"></i>
            Vendre un accessoire
        </div>

        @if($sellableAccessories->isEmpty())
            <p class="text-muted mb-0">Aucun produit actif en stock pour le moment.</p>
        @else
            <form method="POST" action="{{ route('accessories.sell') }}" id="sellForm">
                @csrf

                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label">Produit</label>
                        <select name="accessory_id" id="sellProduct" class="form-select w-100" required>
                            <option value="" disabled selected>Choisir un produit</option>
                            @foreach($sellableAccessories as $a)
                                <option value="{{ $a->id }}" data-price="{{ $a->unit_price }}" data-stock="{{ $a->stock_quantity }}">
                                    {{ $a->name }} — {{ number_format($a->unit_price, 2) }} HTG
                                </option>
                            @endforeach
                        </select>
                        <div class="field-hint" id="sellStockHint"></div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Quantité</label>
                        <input type="number" inputmode="numeric" min="1" step="1" name="quantity" id="sellQuantity" class="form-control w-100" value="1" required>
                    </div>
                </div>

                <div class="row g-3 mt-1 align-items-stretch">
                    <div class="col-md-8">
                        <div class="fee-summary-card" id="sellTotal">
                            <span class="text-muted">Choisissez un produit pour voir le total.</span>
                        </div>
                    </div>
                    <div class="col-md-4 d-flex">
                        <button type="submit" class="btn-tiba-primary w-100" id="sellSubmitBtn" disabled>
                            <i class="bi bi-check-circle me-1"></i> Vendre
                        </button>
                    </div>
                </div>
            </form>
        @endif
    </div>

    <div class="panel">
        <div class="panel-title">Ventes récentes</div>

        @if($recentSales->isEmpty())
            <p class="text-muted mb-0">Aucune vente enregistrée pour le moment.</p>
        @else
            <div class="table-responsive">
                <table class="table table-tiba align-middle">
                    <thead>
                        <tr>
                            <th>Heure</th>
                            <th>Produit</th>
                            <th>Quantité</th>
                            <th>Prix unitaire</th>
                            <th>Total</th>
                            <th>Vendu par</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentSales as $sale)
                            <tr>
                                <td class="text-muted">{{ $sale->created_at->format('H:i') }}</td>
                                <td class="fw-semibold">{{ $sale->accessory->name ?? '—' }}</td>
                                <td>{{ $sale->quantity }}</td>
                                <td>{{ number_format($sale->unit_price, 2) }} HTG</td>
                                <td class="fw-semibold">{{ number_format($sale->total_amount, 2) }} HTG</td>
                                <td class="text-muted">{{ $sale->seller->name ?? '—' }}</td>
                                <td class="text-end">
                                    @if($sale->canBeModifiedBy(auth()->user()))
                                        <button type="button" class="btn-tiba-secondary js-edit-sale" style="padding:0.3rem 0.7rem;font-size:0.8rem;"
                                                data-url="{{ route('accessory-sales.update', $sale) }}"
                                                data-name="{{ $sale->accessory->name ?? '' }}"
                                                data-price="{{ $sale->unit_price }}"
                                                data-quantity="{{ $sale->quantity }}">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div class="modal fade" id="editSaleModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius:16px; border:none;">
                <form method="POST" id="editSaleForm">
                    @csrf
                    @method('PUT')
                    <div class="modal-body p-4">
                        <h5 class="mb-1">Corriger la vente</h5>
                        <p class="text-muted small mb-3" id="editSaleInfo"></p>
                        <label class="form-label">Quantité</label>
                        <input type="number" inputmode="numeric" min="1" name="quantity" id="editSaleQuantity" class="form-control w-100" required>
                        <div class="fee-summary-card mt-3" id="editSaleTotal"></div>
                    </div>
                    <div class="modal-footer border-0 px-4 pb-4 pt-0">
                        <button type="button" class="btn-tiba-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn-tiba-primary">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script src="{{ asset('js/accessories.js') }}"></script>
@endpush
