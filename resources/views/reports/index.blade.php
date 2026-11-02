@extends('layouts.app')

@section('title', 'Rapports')

@section('content')

<style>
    .rp-hero { display:flex; align-items:center; gap:14px; margin-bottom:22px; }
    .rp-hero-icon { width:52px; height:52px; border-radius:14px; background:var(--navy); color:#fff; display:flex; align-items:center; justify-content:center; font-size:1.6rem; flex-shrink:0; }
    .rp-hero h4 { margin:0; font-family:var(--font-heading); color:var(--navy); font-weight:600; }
    .rp-group { font-size:.79rem; letter-spacing:.06em; text-transform:uppercase; color:#6b7a84; font-weight:600; margin:26px 0 10px; display:flex; align-items:center; gap:10px; }
    .rp-group::after { content:""; flex:1; height:1px; background:#dfe5e8; }

    .rp-card { background:#fff; border:1px solid #e3e8ea; border-radius:14px; padding:18px; height:100%; display:flex; flex-direction:column; transition:box-shadow .15s, transform .15s; }
    .rp-card:hover { box-shadow:0 6px 20px rgba(44,74,94,.10); transform:translateY(-1px); }
    .rp-top { display:flex; gap:14px; align-items:flex-start; margin-bottom:14px; }
    .rp-ico { width:44px; height:44px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1.3rem; flex-shrink:0; border:1.5px solid; }
    .rp-ico.c1 { background:#eaf5f4; border-color:#1a7f7a; color:#1a7f7a; }
    .rp-ico.c2 { background:#fff7e3; border-color:#d9a23a; color:#b7791f; }
    .rp-ico.c3 { background:#f3f0f8; border-color:#7d6a9a; color:#7d6a9a; }
    .rp-ico.c4 { background:#eef3f8; border-color:#2C4A5E; color:#2C4A5E; }
    .rp-ico.c5 { background:#eef6e3; border-color:#7CB342; color:#4f8a10; }
    .rp-card h6 { margin:0 0 3px; font-weight:600; color:var(--navy); }
    .rp-card p { margin:0; font-size:.82rem; color:#6b7a84; line-height:1.4; }
    .rp-fields { margin-bottom:14px; }
    .rp-fields .form-label { font-size:.75rem; color:#6b7a84; margin-bottom:3px; }
    .rp-chips { display:flex; flex-wrap:wrap; gap:6px; margin-bottom:12px; }
    .rp-chip { border:1px solid #d3dce0; background:#f6f8f9; color:#2C4A5E; font-size:.74rem; padding:3px 11px; border-radius:20px; cursor:pointer; }
    .rp-chip:hover { background:#e8f0f5; border-color:#2C4A5E; }
    .rp-card .btn { margin-top:auto; width:100%; }
    .rp-foot { margin-top:26px; font-size:.78rem; color:#8a979f; display:flex; align-items:center; gap:6px; }
</style>

<div class="rp-hero">
    <div class="rp-hero-icon"><i class="bi bi-file-earmark-bar-graph"></i></div>
    <div>
        <h4>Rapports PDF</h4>
        <small class="text-muted">Choisissez un rapport</small>
    </div>
</div>

{{-- ───────── ACTIVITÉ ───────── --}}
<div class="rp-group">Activité</div>
<div class="row g-3">

    <div class="col-lg-6">
        <form class="rp-card" method="GET" action="{{ route('reports.daily') }}" target="_blank" data-range-form>
            <div class="rp-top">
                <div class="rp-ico c1"><i class="bi bi-calendar-check"></i></div>
                <div><h6>Clôture journalière</h6><p>Profits, totaux par service, transactions et ventes de la journée.</p></div>
            </div>
            <div class="rp-chips">
                <button type="button" class="rp-chip" data-preset="today">Aujourd'hui</button>
                <button type="button" class="rp-chip" data-preset="yesterday">Hier</button>
            </div>
            <div class="rp-fields">
                <label class="form-label">Date</label>
                <input type="date" name="date" class="form-control" value="{{ now()->toDateString() }}" max="{{ now()->toDateString() }}" required>
            </div>
            <button class="btn btn-tiba-primary"><i class="bi bi-file-earmark-pdf me-1"></i>Générer le PDF</button>
        </form>
    </div>

    <div class="col-lg-6">
        <form class="rp-card" method="GET" action="{{ route('reports.transactions') }}" target="_blank" data-range-form>
            <div class="rp-top">
                <div class="rp-ico c2"><i class="bi bi-arrow-left-right"></i></div>
                <div><h6>Historique des transactions</h6><p>Liste filtrée par période, 92 jours maximum.</p></div>
            </div>
            <div class="rp-chips">
                <button type="button" class="rp-chip" data-preset="today">Aujourd'hui</button>
                <button type="button" class="rp-chip" data-preset="week">7 derniers jours</button>
                <button type="button" class="rp-chip" data-preset="month">Ce mois</button>
                <button type="button" class="rp-chip" data-preset="lastmonth">Mois dernier</button>
            </div>
            <div class="rp-fields">
                <div class="row g-2">
                    <div class="col-6">
                        <label class="form-label">Du</label>
                        <input type="date" name="from" class="form-control" value="{{ now()->startOfMonth()->toDateString() }}" max="{{ now()->toDateString() }}" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label">Au</label>
                        <input type="date" name="to" class="form-control" value="{{ now()->toDateString() }}" max="{{ now()->toDateString() }}" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label">Service</label>
                        <select name="service" class="form-select">
                            <option value="">Tous</option>
                            <option value="moncash">Moncash</option>
                            <option value="natcash">Natcash</option>
                            <option value="zelle">Zelle</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label">Employé</label>
                        <select name="employee" class="form-select">
                            <option value="">Tous</option>
                            @foreach($employees as $e)
                                <option value="{{ $e->id }}">{{ $e->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="form-check mt-3">
                    <input class="form-check-input" type="checkbox" name="include_cancelled" value="1" id="incCancelled">
                    <label class="form-check-label small" for="incCancelled">Inclure les transactions annulées</label>
                </div>
            </div>
            <button class="btn btn-tiba-primary"><i class="bi bi-file-earmark-pdf me-1"></i>Générer le PDF</button>
        </form>
    </div>
</div>

{{-- ───────── DETTES ───────── --}}
<div class="rp-group">Dettes</div>
<div class="row g-3">

    <div class="col-lg-6">
        <div class="rp-card">
            <div class="rp-top">
                <div class="rp-ico c3"><i class="bi bi-cash-coin"></i></div>
                <div><h6>État global des dettes</h6><p>Bilan des créances actives : montant initial, total payé, solde restant.</p></div>
            </div>
            <a href="{{ route('reports.debts') }}" target="_blank" class="btn btn-tiba-primary"><i class="bi bi-file-earmark-pdf me-1"></i>Générer le PDF</a>
        </div>
    </div>

    <div class="col-lg-6">
        <form class="rp-card" method="GET" action="{{ route('reports.client') }}" target="_blank">
            <div class="rp-top">
                <div class="rp-ico c4"><i class="bi bi-person-lines-fill"></i></div>
                <div><h6>Relevé de compte débiteur</h6><p>Dettes en cours d'un débiteur, historique de ses remboursements.</p></div>
            </div>
            <div class="rp-fields">
                <label class="form-label">Débiteur</label>
                <select name="name" class="form-select" required @disabled($debtors->isEmpty())>
                    <option value="">{{ $debtors->isEmpty() ? 'Aucun débiteur enregistré' : 'Choisir un débiteur…' }}</option>
                    @foreach($debtors as $name)
                        <option value="{{ $name }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <button class="btn btn-tiba-primary" @disabled($debtors->isEmpty())><i class="bi bi-file-earmark-pdf me-1"></i>Générer le PDF</button>
        </form>
    </div>
</div>

{{-- ───────── STOCK ───────── --}}
<div class="rp-group">Stock</div>
<div class="row g-3">
    <div class="col-lg-6">
        <form class="rp-card" method="GET" action="{{ route('reports.stock') }}" target="_blank" data-range-form>
            <div class="rp-top">
                <div class="rp-ico c5"><i class="bi bi-box-seam"></i></div>
                <div><h6>Stock et ventes d'accessoires</h6><p>Inventaire actuel et récapitulatif des articles vendus sur la période choisie.</p></div>
            </div>
            <div class="rp-chips">
                <button type="button" class="rp-chip" data-preset="today">Aujourd'hui</button>
                <button type="button" class="rp-chip" data-preset="week">7 derniers jours</button>
                <button type="button" class="rp-chip" data-preset="month">Ce mois</button>
                <button type="button" class="rp-chip" data-preset="lastmonth">Mois dernier</button>
            </div>
            <div class="rp-fields">
                <div class="row g-2">
                    <div class="col-6">
                        <label class="form-label">Du</label>
                        <input type="date" name="from" class="form-control" value="{{ now()->startOfMonth()->toDateString() }}" max="{{ now()->toDateString() }}" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label">Au</label>
                        <input type="date" name="to" class="form-control" value="{{ now()->toDateString() }}" max="{{ now()->toDateString() }}" required>
                    </div>
                </div>
            </div>
            <button class="btn btn-tiba-primary"><i class="bi bi-file-earmark-pdf me-1"></i>Générer le PDF</button>
        </form>
    </div>
</div>

<div class="rp-foot"><i class="bi bi-info-circle"></i> Les transactions annulées sont signalées dans les listes et jamais comptées dans les totaux.</div>

<script>
(function () {
    function ymd(d) {
        return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
    }
    function range(preset) {
        var t = new Date(), from = new Date(), to = new Date();
        if (preset === 'yesterday') { from.setDate(t.getDate() - 1); to = new Date(from); }
        else if (preset === 'week') { from.setDate(t.getDate() - 6); }
        else if (preset === 'month') { from = new Date(t.getFullYear(), t.getMonth(), 1); }
        else if (preset === 'lastmonth') { from = new Date(t.getFullYear(), t.getMonth() - 1, 1); to = new Date(t.getFullYear(), t.getMonth(), 0); }
        return [ymd(from), ymd(to)];
    }
    document.querySelectorAll('[data-range-form]').forEach(function (form) {
        form.querySelectorAll('[data-preset]').forEach(function (chip) {
            chip.addEventListener('click', function () {
                var r = range(chip.dataset.preset);
                var single = form.querySelector('[name="date"]');
                if (single) { single.value = r[0]; return; }
                form.querySelector('[name="from"]').value = r[0];
                form.querySelector('[name="to"]').value = r[1];
            });
        });
    });
})();
</script>

@endsection
