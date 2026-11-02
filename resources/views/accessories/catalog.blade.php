@extends('layouts.app')

@section('title', 'Catalogue')

@section('content')

    <div class="panel">
        <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
            <div class="panel-title mb-0">Catalogue</div>
            <button type="button" class="btn-tiba-secondary flex-shrink-0" data-bs-toggle="modal" data-bs-target="#addProductModal">
                <i class="bi bi-plus-lg me-1"></i> Nouveau produit
            </button>
        </div>

        @if($accessories->isEmpty())
            <p class="text-muted mb-0">Aucun produit enregistré.</p>
        @else
            <div class="table-responsive">
                <table class="table table-tiba align-middle">
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Prix</th>
                            <th>Stock</th>
                            <th>Statut</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($accessories as $a)
                            <tr>
                                <td class="fw-semibold">{{ $a->name }}</td>
                                <td>{{ number_format($a->unit_price, 2) }} HTG</td>
                                <td>
                                    {{ $a->stock_quantity }}
                                    @if($a->stock_quantity <= 0)
                                        <span class="status-annulee ms-1">Épuisé</span>
                                    @elseif($a->stock_quantity <= $a->low_stock_threshold)
                                        <span class="status-warning ms-1">Stock bas</span>
                                    @endif
                                </td>
                                <td>
                                    @if($a->status === 'actif')
                                        <span class="status-validee">Actif</span>
                                    @else
                                        <span class="status-inactif">Inactif</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="d-flex gap-2 justify-content-end">
                                        @if($a->status === 'actif')
                                            <button type="button" class="btn-tiba-secondary js-edit-product"
                                                    style="padding:0.3rem 0.7rem;font-size:0.8rem;"
                                                    data-id="{{ $a->id }}" data-name="{{ $a->name }}"
                                                    data-description="{{ $a->description }}" data-price="{{ $a->unit_price }}"
                                                    data-threshold="{{ $a->low_stock_threshold }}"
                                                    data-url="{{ route('accessories.update', $a) }}">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button type="button" class="btn-tiba-secondary js-adjust-stock"
                                                    style="padding:0.3rem 0.7rem;font-size:0.8rem;"
                                                    data-id="{{ $a->id }}" data-name="{{ $a->name }}" data-stock="{{ $a->stock_quantity }}"
                                                    data-url="{{ route('accessories.adjust-stock', $a) }}">
                                                <i class="bi bi-box-seam"></i>
                                            </button>
                                        @endif
                                        <form method="POST" action="{{ route('accessories.toggle', $a) }}"
                                              class="{{ $a->status === 'actif' ? 'js-confirm-toggle' : '' }}">
                                            @csrf
                                            <button type="submit" class="{{ $a->status === 'actif' ? 'btn-tiba-danger' : 'btn-tiba-secondary' }}"
                                                    style="padding:0.3rem 0.7rem;font-size:0.8rem;">
                                                <i class="bi bi-{{ $a->status === 'actif' ? 'slash-circle' : 'arrow-counterclockwise' }}"></i>
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

    {{-- ── Modale : nouveau produit ──────────────────────────────────────── --}}
    <div class="modal fade" id="addProductModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius:16px; border:none;">
                <form method="POST" action="{{ route('accessories.store') }}">
                    @csrf
                    <div class="modal-body p-4">
                        <h5 class="mb-3">Nouveau produit</h5>
                        <div class="mb-3">
                            <label class="form-label">Nom</label>
                            <input type="text" name="name" class="form-control w-100" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description (optionnelle)</label>
                            <input type="text" name="description" class="form-control w-100">
                        </div>
                        <div class="row g-3">
                            <div class="col-6">
                                <label class="form-label">Prix</label>
                                <input type="number" step="0.01" min="0" inputmode="decimal" name="unit_price" class="form-control w-100" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label">Stock initial</label>
                                <input type="number" min="0" inputmode="numeric" name="stock_quantity" class="form-control w-100" required>
                            </div>
                        </div>
                        <div class="mb-3 mt-3">
                            <label class="form-label">Seuil de stock bas</label>
                            <input type="number" min="1" inputmode="numeric" name="low_stock_threshold" class="form-control w-100" value="5" required>
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

    {{-- ── Modale : modifier produit (remplie par JS) ─────────────────────── --}}
    <div class="modal fade" id="editProductModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius:16px; border:none;">
                <form method="POST" id="editProductForm">
                    @csrf
                    @method('PUT')
                    <div class="modal-body p-4">
                        <h5 class="mb-3">Modifier le produit</h5>
                        <div class="mb-3">
                            <label class="form-label">Nom</label>
                            <input type="text" name="name" id="editName" class="form-control w-100" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description (optionnelle)</label>
                            <input type="text" name="description" id="editDescription" class="form-control w-100">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Prix</label>
                            <input type="number" step="0.01" min="0" inputmode="decimal" name="unit_price" id="editPrice" class="form-control w-100" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Seuil de stock bas</label>
                            <input type="number" min="1" inputmode="numeric" name="low_stock_threshold" id="editThreshold" class="form-control w-100" required>
                        </div>
                    </div>
                    <div class="modal-footer border-0 px-4 pb-4 pt-0">
                        <button type="button" class="btn-tiba-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn-tiba-primary">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ── Modale : ajuster le stock (remplie par JS) ─────────────────────── --}}
    <div class="modal fade" id="adjustStockModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius:16px; border:none;">
                <form method="POST" id="adjustStockForm">
                    @csrf
                    <div class="modal-body p-4">
                        <h5 class="mb-1">Ajuster le stock</h5>
                        <p class="text-muted small mb-3" id="adjustStockProductName"></p>

                        <div class="movement-btn-group mb-3">
                            <button type="button" class="movement-btn active" data-type="add">Ajouter</button>
                            <button type="button" class="movement-btn" data-type="remove">Retirer</button>
                        </div>
                        <input type="hidden" name="type" id="adjustType" value="add">

                        <label class="form-label">Quantité</label>
                        <input type="number" min="1" inputmode="numeric" name="adjustment" id="adjustQuantity" class="form-control w-100" required>
                    </div>
                    <div class="modal-footer border-0 px-4 pb-4 pt-0">
                        <button type="button" class="btn-tiba-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn-tiba-primary">Confirmer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script src="{{ asset('js/accessories.js') }}"></script>
@endpush
