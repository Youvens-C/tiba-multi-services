@use('App\Support\Fmt')
@php
    $valid = $d->payments->where('status', 'valide');
    $paid = $valid->sum('amount_applied');
@endphp
<div class="debt-block">
    <table class="debt-head">
        <tr>
            <td>
                <span class="debt-title">{{ $d->reason ?: 'Dette' }}</span><br>
                <span class="debt-date">Dette du {{ $d->created_at->format('d/m/Y') }}</span>
            </td>
            <td class="right"><span class="pill st-active">En cours</span></td>
        </tr>
    </table>

    <table class="figs">
        <tr>
            <td>
                <div class="fig f-init">
                    <div class="fig-label">Montant de la dette</div>
                    <div class="fig-value">{{ Fmt::money($d->initial_amount, $d->currency) }}</div>
                </div>
            </td>
            <td><div class="fig f-paid">
                <div class="fig-label">Déjà remboursé</div>
                <div class="fig-value">{{ Fmt::money($paid, $d->currency) }}</div>
            </div></td>
            <td><div class="fig f-due">
                <div class="fig-label">Reste à payer</div>
                <div class="fig-value big">{{ Fmt::money($d->remaining_balance, $d->currency) }}</div>
            </div></td>
        </tr>
    </table>

    @if($valid->isEmpty())
        <div class="legend">Aucun remboursement pour le moment.</div>
    @else
    <table class="data">
        <thead>
            <tr>
                <th>Remboursements</th>
                <th class="right">Montant</th>
                <th class="right">Reçu par</th>
            </tr>
        </thead>
        <tbody>
            @foreach($valid as $p)
            <tr class="{{ $loop->even ? 'alt' : '' }}">
                <td class="nowrap">{{ $p->created_at->format('d/m/Y à H:i') }}</td>
                <td class="right nowrap bold">{{ Fmt::money($p->amount_applied, $d->currency) }}</td>
                <td class="right">{{ $p->creator->name ?? '—' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif
</div>
