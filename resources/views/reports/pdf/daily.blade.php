@use('App\Support\Fmt')
@extends('reports.pdf.layout')

@section('title', 'Rapport du jour')
@section('subtitle', $day->locale('fr')->isoFormat('dddd D MMMM YYYY'))

@section('content') 
 
<div class="marge"></div>

<table class="kpis">
    <tr>
        <td><div class="kpi k1"><div class="label">Profit net du jour (HTG)</div><div class="value">{{ Fmt::money($profitHtg) }}</div></div></td>
        <td><div class="kpi k2"><div class="label">Profit net du jour (USD)</div><div class="value">{{ Fmt::money($profitUsd, 'USD') }}</div></div></td>
        <td><div class="kpi k3"><div class="label">Ventes d'accessoires</div><div class="value">{{ Fmt::money($salesTotal) }}</div></div></td>
        <td><div class="kpi k4"><div class="label">Transactions validées</div><div class="value">{{ $validCount }}</div>
            @if($cancelCount)<div class="extra">{{ $cancelCount }} annulée(s)</div>@endif</div></td>
    </tr>
</table>

<h2>1. Totaux par service</h2>
<table class="data">
    <thead>
        <tr>
            <th>Service</th>
            <th class="right">Transactions</th>
            <th class="right">Montants traités</th>
            <th class="right">Profit net entreprise</th>
        </tr>
    </thead>
    <tbody>
        @foreach($services as $s)
        <tr>
            <td class="bold">{{ ucfirst($s['service']) }} ({{ $s['currency'] }})</td>
            <td class="right">{{ $s['count'] }}</td>
            <td class="right">{{ Fmt::money($s['volume'], $s['currency']) }}</td>  
            <td class="right bold">{{ Fmt::money($s['fee_net'], $s['currency']) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

@if(count($balances))
<h2>2. Soldes téléphone</h2>
<table class="data">
    <thead>
        <tr>
            <th>Service</th>
            <th class="right">Solde d'ouverture</th>
            <th class="right">Retraits</th>
            <th class="right">Transferts / dépôts</th>
            <th class="right">Solde théorique</th>
        </tr>
    </thead>
    <tbody>
        @foreach($balances as $service => $b)
        <tr>
            <td class="bold">{{ ucfirst($service) }}</td>
            <td class="right">{{ Fmt::money($b['opening']) }}</td>
            <td class="right">{{ Fmt::money($b['in']) }}</td>
            <td class="right">{{ Fmt::money($b['out']) }}</td>
            <td class="right bold">{{ Fmt::money($b['theoretical']) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

<h2>3. Transactions de la journée</h2>
@if($transactions->isEmpty())
    <div class="empty">Aucune transaction enregistrée ce jour.</div>
@else
<table class="data">
    <thead>
        <tr>
            <th>Heure</th>
            <th>Service</th>
            <th>Client</th>
            <th class="right">Montant</th>
            <th class="right">Profit net</th>
            <th>Employé</th>
        </tr>
    </thead>
    <tbody>
        @foreach($transactions as $t)
        <tr class="{{ $t->status === 'annulee' ? 'cancelled' : ($loop->even ? 'alt' : '') }}">
            <td class="nowrap">{{ $t->created_at->format('H:i') }}</td>
            <td>{{ ucfirst($t->service) }}<div class="sub">{{ $t->movement_label ?? $t->movement_type }}@if($t->status === 'annulee') · annulée @endif</div></td>
            <td class="nowrap">{{ $t->client_phone ?: '—' }}</td>
            <td class="right nowrap">{{ Fmt::num($t->amount) }}<div class="sub">{{ $t->currency }}</div></td>  
            <td class="right nowrap">{{ Fmt::num($t->fee_net) }}<div class="sub">{{ $t->currency }}</div></td>
            <td>{{ $t->creator->name ?? '—' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

@endif

<h2>4. Ventes d'accessoires de la journée</h2>
@if($sales->isEmpty())
    <div class="empty">Aucune vente d'accessoire ce jour.</div>
@else
<table class="data">
    <thead>
        <tr>
            <th>Heure</th>
            <th>Article</th>
            <th class="right">Qté</th>
            <th class="right">Prix unitaire</th>
            <th class="right">Total</th>
            <th>Vendeur</th>
        </tr>
    </thead>
    <tbody>
        @foreach($sales as $sale)
        <tr>
            <td class="nowrap">{{ $sale->created_at->format('H:i') }}</td>
            <td>{{ $sale->accessory->name ?? '—' }}</td>
            <td class="right">{{ $sale->quantity }}</td>
            <td class="right nowrap">{{ Fmt::money($sale->unit_price) }}</td>
            <td class="right nowrap">{{ Fmt::money($sale->total_amount) }}</td>
            <td>{{ $sale->seller->name ?? '—' }}</td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr><td colspan="4" class="right">Total des ventes</td><td class="right nowrap">{{ Fmt::money($salesTotal) }}</td><td></td></tr>
    </tfoot>
</table>
@endif

@endsection
