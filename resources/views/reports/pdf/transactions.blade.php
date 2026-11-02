@use('App\Support\Fmt')
@extends('reports.pdf.layout')

@section('title', 'Historique des transactions')
@section('subtitle', 'Du ' . $start->format('d/m/Y') . ' au ' . $end->format('d/m/Y') . ' · ' . $serviceLabel . ' · ' . $employeeName)

@section('content')

<div class="marge"></div>
<table class="kpis">
    <tr>
        <td><div class="kpi k1"><div class="label">Profit net (HTG)</div><div class="value">{{ Fmt::money($profitHtg) }}</div></div></td>
        <td><div class="kpi k2"><div class="label">Profit net (USD)</div><div class="value">{{ Fmt::money($profitUsd, 'USD') }}</div></div></td>
        <td><div class="kpi k3"><div class="label">Transactions validées</div><div class="value">{{ $totals->sum('count') }}</div></div></td>
        <td><div class="kpi k4"><div class="label">Annulées {{ $includeCancelled ? '(listées)' : '(masquées)' }}</div><div class="value">{{ $includeCancelled ? $cancelCount : '—' }}</div></div></td>
    </tr>
</table> 

<h2>1. Totaux par service</h2>
@if($totals->isEmpty())
    <div class="empty">Aucune transaction validée sur cette période.</div>
@else
<table class="data">
    <thead>
        <tr><th>Service</th><th class="right">Transactions</th>
        <th class="right">Montants traités</th>  
        <th class="right">Profit net</th></tr>
    </thead>
    <tbody>
        @foreach($totals as $service => $t)
        <tr>
            <td class="bold">{{ ucfirst($service) }} ({{ $t['currency'] }})</td>
            <td class="right">{{ $t['count'] }}</td>
            <td class="right">{{ Fmt::money($t['volume'], $t['currency']) }}</td>    
            <td class="right bold">{{ Fmt::money($t['fee_net'], $t['currency']) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

<h2>2. Détail des transactions</h2>
@if($transactions->isEmpty())
    <div class="empty">Aucune transaction.</div>
@else
<table class="data">
    <thead>
        <tr>
            <th>Date</th>
            <th>Service</th>
            <th>Client</th>
            <th class="right">Montant</th>
            <th class="right">Profit net</th>
            <th class="right">Employé</th>
        </tr>
    </thead>
    <tbody>
        @foreach($transactions as $t)
        <tr class="{{ $t->status === 'annulee' ? 'cancelled' : ($loop->even ? 'alt' : '') }}">
            <td class="nowrap">{{ $t->created_at->format('d/m/Y') }}<div class="sub">{{ $t->created_at->format('H:i') }}</div></td>
            <td>{{ ucfirst($t->service) }}<div class="sub">{{ $t->movement_label ?? $t->movement_type }}@if($t->status === 'annulee') · annulée @endif</div></td>
            <td class="nowrap">{{ $t->client_phone ?: '—' }}</td>
            <td class="right nowrap">{{ Fmt::num($t->amount) }}<div class="sub">{{ $t->currency }}</div></td>     
            <td class="right nowrap">{{ Fmt::num($t->fee_net) }}<div class="sub">{{ $t->currency }}</div></td>
            <td class="right nowrap">{{ $t->creator->name ?? '—' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

@endsection
