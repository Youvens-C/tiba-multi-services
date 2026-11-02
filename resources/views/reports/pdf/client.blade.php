@use('App\Support\Fmt')
@extends('reports.pdf.layout')

@section('title', 'Relevé de compte client')
@section('subtitle', $name)

@section('content')

{{-- ───────── 1. CE QUE LE CLIENT DOIT ───────── --}}
<h2>1. Situation actuelle</h2>

@if($totals->isEmpty())
<div class="uptodate">
    <span class="bold">Compte à jour</span> — {{ $name }} n'a aucune dette en cours.
</div>
@else
<table class="data">
    <thead>
        <tr>
            <th>Devise</th>
            <th class="right">Dettes en cours</th>
            <th class="right">Total emprunté</th>
            <th class="right">Total remboursé</th>
            <th class="right">Solde restant dû</th>
        </tr>
    </thead>
    <tbody>
        @foreach($totals as $currency => $t)
        <tr>
            <td class="bold">{{ $currency }}</td>
            <td class="right">{{ $t['count'] }}</td>
            <td class="right">{{ Fmt::money($t['initial'], $currency) }}</td>
            <td class="right">{{ Fmt::money($t['paid'], $currency) }}</td>
            <td class="right bold">{{ Fmt::money($t['remaining'], $currency) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

{{-- ───────── 2. DETTES EN COURS ───────── --}}
@if($open->isNotEmpty())
<h2>2. Dettes en cours</h2>
@foreach($open as $d)
    @include('reports.pdf._debt', ['d' => $d])
@endforeach
@endif

{{-- ───────── 3. HISTORIQUE ───────── --}}
@if($closed->isNotEmpty())
<h2>{{ $open->isNotEmpty() ? '3' : '2' }}. Historique des dettes clôturées</h2>
@php
    $labels = ['soldee' => 'Soldée', 'abandonnee' => 'Abandonnée', 'annulee' => 'Annulée'];
@endphp
<table class="data">
    <thead>
        <tr>
            <th>Date</th>
            <th>Motif</th>
            <th class="right">Montant</th>
            <th class="right">Remboursé</th>
            <th>Clôture</th>
            <th class="right">Statut</th>
        </tr>
    </thead>
    <tbody>
        @foreach($closed as $d)
        @php $paid = $d->payments->where('status', 'valide')->sum('amount_applied'); @endphp
        <tr class="{{ $loop->even ? 'alt' : '' }}">
            <td class="nowrap">{{ $d->created_at->format('d/m/Y') }}</td>
            <td class="bold">{{ $d->reason ?: '—' }}</td>
            <td class="right nowrap">{{ Fmt::money($d->initial_amount, $d->currency) }}</td>
            <td class="right nowrap">{{ Fmt::money($paid, $d->currency) }}</td>
            <td class="nowrap">{{ $d->closed_at ? $d->closed_at->format('d/m/Y') : '—' }}</td>
            <td class="right"><span class="stt t-{{ $d->status }}">{{ $labels[$d->status] ?? $d->status }}</span></td>
        </tr>
        @endforeach
    </tbody>
</table>
<div class="legend">
    <b>Soldée</b> : entièrement remboursée. <b>Abandonnée</b> : le solde restant n'est plus réclamé.
    <b>Annulée</b> : dette enregistrée par erreur.
</div>
@endif

@endsection
