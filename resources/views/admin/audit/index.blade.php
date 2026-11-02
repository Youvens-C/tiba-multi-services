@extends('layouts.app')

@section('title', 'Journal d\'audit')

@section('content')

<style>
    .au-tone-danger  { color:#c0392b; }
    .au-tone-warning { color:#b7791f; }
    .au-tone-success { color:#4f8a10; }
    .au-tone-neutral { color:#44525b; }
    .au-action { font-weight:600; }
    .au-entity { font-size:.85rem; color:#1f2d36; }
    .au-change { font-size:.85rem; margin-top:3px; }
    .au-field { color:#44525b; }
    .au-old { text-decoration:line-through; color:#8a979f; }
    .au-new { font-weight:600; color:#1f2d36; }
    .au-details { font-size:.82rem; color:#44525b; margin-top:3px; }
    .au-when { white-space:nowrap; font-size:.85rem; }
    .au-when small { display:block; color:#6b7a84; }
    .au-card { border:1px solid #e3e8ea; border-radius:12px; padding:12px 14px; background:#fff; margin-bottom:10px; }
    .au-filters .form-label { font-size:.75rem; color:#6b7a84; margin-bottom:3px; }
    .au-actions { display:flex; flex-wrap:wrap; gap:8px; }
    .au-actions .btn { white-space:nowrap; }
</style>

<div class="d-flex align-items-center mb-3 gap-3 flex-wrap">
    <div class="d-flex align-items-center gap-2 me-auto">
        <i class="bi bi-shield-check fs-4" style="color:var(--navy)"></i>
        <div>
            <h5 class="mb-0" style="font-family:var(--font-heading)">Journal d'audit</h5>
            <small class="text-muted">{{ number_format($logs->total(), 0, ',', ' ') }} entrée(s){{ $hasFilters ? ' avec ces filtres' : '' }} · lecture seule</small>
        </div>
    </div>
</div>

{{-- Filtres --}}
<form method="GET" action="{{ route('admin.audit') }}" class="panel p-3 mb-3 au-filters">
    <div class="row g-2">
        <div class="col-6 col-md-3 col-xl-2">
            <label class="form-label">Du</label>
            <input type="date" name="from" class="form-control" value="{{ $filters['from'] }}" max="{{ now()->toDateString() }}">
        </div>
        <div class="col-6 col-md-3 col-xl-2">
            <label class="form-label">Au</label>
            <input type="date" name="to" class="form-control" value="{{ $filters['to'] }}" max="{{ now()->toDateString() }}">
        </div>
        <div class="col-12 col-md-6 col-xl-2">
            <label class="form-label">Utilisateur</label>
            <select name="user_id" class="form-select">
                <option value="">Tous</option>
                @foreach($users as $u)
                    <option value="{{ $u->id }}" @selected($filters['user_id'] == $u->id)>{{ $u->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <label class="form-label">Action</label>
            <select name="action_type" class="form-select">
                <option value="">Toutes</option>
                @foreach($actionTypes as $a)
                    <option value="{{ $a }}" @selected($filters['action_type'] === $a)>{{ \App\Models\AuditLog::actionLabel($a) }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <label class="form-label">Élément</label>
            <select name="entity_type" class="form-select">
                <option value="">Tous</option>
                @foreach($entityTypes as $e)
                    <option value="{{ $e }}" @selected($filters['entity_type'] === $e)>{{ \App\Models\AuditLog::entityLabelOf($e) }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-12">
            <label class="form-label">Recherche (nom du débiteur, produit, numéro, montant…)</label>
            <input type="text" name="q" class="form-control" value="{{ $filters['q'] }}" maxlength="100" placeholder="Ex. Joseline">
        </div>
    </div>
    <div class="au-actions mt-3">
        <button class="btn btn-tiba-primary"><i class="bi bi-funnel me-1"></i>Filtrer</button>
        @if($hasFilters)
            <a href="{{ route('admin.audit') }}" class="btn btn-tiba-secondary"><i class="bi bi-x-circle me-1"></i>Réinitialiser</a>
        @endif
        <a href="{{ route('admin.audit.pdf', request()->query()) }}" target="_blank" class="btn btn-tiba-secondary ms-md-auto"><i class="bi bi-file-earmark-pdf me-1"></i>Exporter en PDF</a>
    </div>
</form>

@if($logs->isEmpty())
    <div class="panel p-4 text-center text-muted">
        <i class="bi bi-journal-x fs-3 d-block mb-2"></i>
        Aucune entrée {{ $hasFilters ? 'pour ces filtres.' : 'dans le journal.' }}
    </div>
@else

{{-- Tableau (écrans moyens et grands) --}}
<div class="panel d-none d-md-block">
    <div class="table-responsive">
        <table class="table table-tiba mb-0 align-middle">
            <thead>
                <tr>
                    <th>Date et heure</th>
                    <th>Utilisateur</th>
                    <th>Action</th>
                    <th>Élément concerné et détails</th>
                </tr>
            </thead>
            <tbody>
                @foreach($logs as $log)
                <tr>
                    <td class="au-when">{{ $log->created_at->format('d/m/Y') }}<small>{{ $log->created_at->format('H:i:s') }}</small></td>
                    <td>{{ $log->user->name ?? '—' }}</td>
                    <td><span class="au-action au-tone-{{ $log->action_tone }}">{{ $log->action_name }}</span></td>
                    <td>
                        <div class="au-entity">{{ $log->entity_label ?: \App\Models\AuditLog::entityLabelOf($log->entity_type) . ' n°' . $log->entity_id }}</div>
                        @include('admin.audit._changes', ['log' => $log])
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- Cartes (smartphone) --}}
<div class="d-md-none">
    @foreach($logs as $log)
    <div class="au-card">
        <div class="d-flex justify-content-between gap-2">
            <span class="au-action au-tone-{{ $log->action_tone }}">{{ $log->action_name }}</span>
            <span class="au-when text-end">{{ $log->created_at->format('d/m/Y H:i:s') }}</span>
        </div>
        <div class="small text-muted mb-1">par {{ $log->user->name ?? '—' }}</div>
        <div class="au-entity">{{ $log->entity_label ?: \App\Models\AuditLog::entityLabelOf($log->entity_type) . ' n°' . $log->entity_id }}</div>
        @include('admin.audit._changes', ['log' => $log])
    </div>
    @endforeach
</div>

<div class="mt-3">
    {{ $logs->links('pagination::bootstrap-5') }}
</div>
@endif

@endsection
