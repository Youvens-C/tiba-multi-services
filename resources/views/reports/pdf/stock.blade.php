@use('App\Support\Fmt')
@extends('reports.pdf.layout')

@section('title', 'Stock et ventes d\'accessoires')
@section('subtitle', 'Inventaire au ' . $generatedAt->format('d/m/Y') . ' · Ventes du ' . $start->format('d/m/Y') . ' au ' . $end->format('d/m/Y'))

@section('content')

<div class="marge"></div>
<table class="kpis">
    <tr>
        <td><div class="kpi k1"><div class="label">Articles au catalogue</div><div class="value">{{ $accessories->count() }}</div></div></td>
        <td><div class="kpi k2"><div class="label">Valeur du stock (actifs)</div><div class="value">{{ Fmt::money($stockValue) }}</div></div></td>
        <td><div class="kpi k3"><div class="label">Unités vendues</div><div class="value">{{ $soldQty }}</div></div></td>
        <td><div class="kpi k4"><div class="label">Chiffre d'affaires</div><div class="value">{{ Fmt::money($soldAmount) }}</div></div></td>
    </tr>
</table>
 
<h2>1. Inventaire </h2>
@if($accessories->isEmpty())
    <div class="empty">Aucun article au catalogue.</div>
@else
<table class="data">
    <thead>
        <tr>
            <th>Article</th>
            <th class="right">Prix unitaire</th>
            <th class="right">Stock</th>
            <th class="right">État du stock</th>  
            <th class="right">Statut</th>
        </tr>
    </thead>
    <tbody>
        @foreach($accessories as $a)
        <tr>
            <td class="bold">{{ $a->name }}</td>
            <td class="right nowrap">{{ Fmt::money($a->unit_price) }}</td>
            <td class="right nowrap">{{ $a->stock_quantity }}</td>
            <td class="right nowrap">
                @if($a->stock_quantity <= 0)<span class="tag bad">Rupture</span>
                @elseif($a->stock_quantity <= $a->low_stock_threshold)<span class="tag warn">Stock bas</span>
                @else<span class="tag ok">OK</span>@endif
            </td>
            <td class="right nowrap">{{ $a->status === 'actif' ? 'Actif' : 'Inactif' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

<h2>2. Ventes de la période</h2>
@php $sold = $accessories->filter(fn ($a) => (int) $a->sold_qty > 0); @endphp
@if($sold->isEmpty())
    <div class="empty">Aucune vente sur cette période.</div>
@else
<table class="data">
    <thead>
        <tr>
            <th>Article</th>
            <th class="right">Prix unitaire</th>
            <th class="right">Qté vendue</th>
            <th class="right">Montant vendu</th>
            <th>Vendeur(s)</th>
        </tr>
    </thead>
    <tbody>
        @foreach($sold as $a)
        <tr class="{{ $loop->even ? 'alt' : '' }}">
            <td class="bold">{{ $a->name }}</td>
            <td class="right nowrap">{{ Fmt::money($a->unit_price) }}</td>
            <td class="right">{{ (int) $a->sold_qty }}</td>
            <td class="right nowrap">{{ Fmt::money($a->sold_amount ?? 0) }}</td>
            <td>{{ $sellers[$a->id] ?? '—' }}</td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td colspan="2" class="right">Total de la période</td>
            <td class="right">{{ $soldQty }}</td>
            <td class="right nowrap">{{ Fmt::money($soldAmount) }}</td>
            <td></td>
        </tr>
    </tfoot>
</table>
@endif

@endsection
