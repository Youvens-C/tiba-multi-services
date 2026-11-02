{{-- Détail d'une entrée : avant → après, sinon texte libre --}}
@if(count($log->change_lines))
    @foreach($log->change_lines as $c)
        <div class="au-change"><span class="au-field">{{ $c['label'] }}</span> <span class="au-old">{{ $c['old'] }}</span> <i class="bi bi-arrow-right"></i> <span class="au-new">{{ $c['new'] }}</span></div>
    @endforeach
@endif
@if($log->details)
    <div class="au-details">{{ $log->details }}</div>
@endif
