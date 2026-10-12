@extends('pulse.layout')

@section('content')
    <main class="screen">
        <div class="notice notice-warn">
            <strong>{{ \App\Support\Pulse\PulseFormat::integer($alerts['unread']) }} {{ $alerts['unread'] === 1 ? 'alerta sin leer' : 'alertas sin leer' }}</strong>
            <span class="muted">{{ $alerts['internal_unread'] }} interna · {{ $alerts['google_unread'] }} Google</span>
        </div>
        <nav class="filters">
            <a class="btn {{ $filter === 'todas' ? 'is-on' : 'btn-ghost' }}" href="{{ request()->url() }}?filtro=todas">Todas</a>
            <a class="btn {{ $filter === 'sin_leer' ? 'is-on' : 'btn-ghost' }}" href="{{ request()->url() }}?filtro=sin_leer">Sin leer</a>
        </nav>
        @if($alerts['items'] === [])
            <section class="card">@include('pulse.partials.empty', ['message' => 'No hay alertas'])</section>
        @else
            @foreach($alerts['items'] as $item)
                <details class="alert" @if($item['read_url']) data-read="{{ $item['read_url'] }}" @endif>
                    <summary>
                        <span>
                            @if($item['unread'])<span class="dot"></span>@endif
                            <span class="tag tag-{{ $item['tone'] }}">{{ $item['tone'] === 'google' ? 'GOOGLE' : 'INTERNA' }}</span><br>
                            <strong>{{ $item['title'] }}</strong>
                        </span>
                        <span class="date">{{ $item['date'] }}</span>
                    </summary>
                    <div class="body">
                        <p>{{ $item['body'] }}</p>
                        <p class="muted">Fecha: {{ $item['date'] }}</p>
                        @if($item['link'])
                            <a class="banner" href="{{ $item['link'] }}" target="_blank" rel="noopener">Ver últimas reseñas en Google</a>
                        @endif
                    </div>
                </details>
            @endforeach
        @endif
    </main>
@endsection

@push('scripts')
<script>
document.querySelectorAll('details.alert[data-read]').forEach(function (item) {
    item.addEventListener('toggle', function () {
        if (!item.open || item.dataset.sent) return;
        item.dataset.sent = '1';
        fetch(item.dataset.read, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            }
        }).catch(function () {});
    });
});
</script>
@endpush
