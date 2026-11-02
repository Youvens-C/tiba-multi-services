@use('App\Support\Fmt')
@extends('reports.pdf.layout')

@section('title', 'État global des dettes')
@section('subtitle', 'Dettes actives au ' . $generatedAt->format('d/m/Y'))

@section('content')

<h2>1. Totaux à recouvrer</h2>
@if($totals->isEmpty())
    <div class="empty">Aucune dette active.</div>
@else
<table class="data">
    <thead>
        <tr><th>Devise</th><th class="right">Dettes</th><th class="right">Montant initial</th><th class="right">Total payé</th><th class="right">Reste à recouvrer</th></tr>
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

<h2>2. Détail des dettes actives</h2>
<table class="data">
    <thead>
        <tr>
            <th>Débiteur</th><th>Motif</th><th>Date</th>
            <th class="right">Montant initial</th><th class="right">Total payé</th><th class="right">Solde restant dû</th>
        </tr>
    </thead>
    <tbody>
        @foreach($debts as $d)
        <tr>
            <td class="bold">{{ $d->debtor_name }}</td>
            <td>{{ $d->reason ?: '—' }}</td>
            <td class="nowrap">{{ $d->created_at->format('d/m/Y') }}</td>
            <td class="right nowrap">{{ Fmt::money($d->initial_amount, $d->currency) }}</td>
            <td class="right nowrap">{{ Fmt::money($d->paid_sum ?? 0, $d->currency) }}</td>
            <td class="right nowrap bold">{{ Fmt::money($d->remaining_balance, $d->currency) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

@endsection
