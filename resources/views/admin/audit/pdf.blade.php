@extends('reports.pdf.layout')

@section('title', 'Journal d\'audit')
@section('subtitle', $subtitle)

@section('content')

<h2>{{ $logs->count() }} entrée(s)</h2>

@if($logs->isEmpty())
    <div class="empty">Aucune entrée pour ces filtres.</div>
@else
<table class="data">
    <thead>
        <tr>
            <th style="width:17%;">Date et heure</th>
            <th style="width:15%;">Utilisateur</th>
            <th style="width:20%;">Action</th>
            <th>Élément concerné et détails</th>
        </tr>
    </thead>
    <tbody>
        @foreach($logs as $log)
        <tr class="{{ $loop->even ? 'alt' : '' }}">
            <td class="nowrap">{{ $log->created_at->format('d/m/Y') }}<div class="sub">{{ $log->created_at->format('H:i:s') }}</div></td>
            <td>{{ $log->user->name ?? '—' }}</td>
            <td class="bold">{{ $log->action_name }}</td>
            <td>
                {{ $log->entity_label ?: \App\Models\AuditLog::entityLabelOf($log->entity_type) . ' n°' . $log->entity_id }}
                @foreach($log->change_lines as $c)
                    <div class="sub" style="color:#1f2d36;"><b>{{ $c['label'] }}</b> : {{ $c['old'] }} → <b>{{ $c['new'] }}</b></div>
                @endforeach
                @if($log->details)<div class="sub">{{ $log->details }}</div>@endif
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

@endsection
