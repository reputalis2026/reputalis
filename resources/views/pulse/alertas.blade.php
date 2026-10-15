@extends('pulse.layout')

@section('content')
    <main class="screen">
        <div class="notice notice-warn">
            <strong id="alert-unread-total" data-count="{{ $alerts['unread'] }}">{{ \App\Support\Pulse\PulseFormat::integer($alerts['unread']) }} {{ $alerts['unread'] === 1 ? 'alerta sin leer' : 'alertas sin leer' }}</strong>
            <span class="muted"><span id="alert-unread-internal">{{ $alerts['internal_unread'] }}</span> interna · <span id="alert-unread-google">{{ $alerts['google_unread'] }}</span> Google</span>
        </div>
        <nav class="filters">
            <a class="btn {{ $filter === 'todas' ? 'is-on' : 'btn-ghost' }}" href="{{ request()->url() }}?filtro=todas">Todas</a>
            <a class="btn {{ $filter === 'sin_leer' ? 'is-on' : 'btn-ghost' }}" href="{{ request()->url() }}?filtro=sin_leer">Sin leer</a>
        </nav>
        @if($alerts['items'] === [])
            <section class="card">@include('pulse.partials.empty', ['message' => 'No hay alertas'])</section>
        @else
            @foreach($alerts['items'] as $item)
                <details class="alert {{ $item['unread'] ? 'is-unread' : '' }}" data-tone="{{ $item['tone'] }}" @if($item['read_url']) data-read="{{ $item['read_url'] }}" @endif>
                    <summary>
                        <span class="alert-kicker">
                            <span>
                                @if($item['unread'])<span class="dot"></span>@endif
                                <span class="tag tag-{{ $item['tone'] }}">{{ $item['tone'] === 'google' ? 'GOOGLE' : 'INTERNA' }}</span>
                            </span>
                            <span class="date">{{ $item['date'] }}</span>
                        </span>
                        <span class="alert-title">
                            <strong>{{ $item['title'] }}</strong>
                            <svg class="alert-chev" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>
                        </span>
                    </summary>
                    <div class="body">
                        @if($item['kind'] === 'internal')
                            <dl class="alert-fields">
                                <div><dt>Fecha</dt><dd>{{ $item['date'] }}</dd></div>
                                <div><dt>Hora</dt><dd>{{ $item['time'] }}</dd></div>
                                <div><dt>Incidencia</dt><dd>{{ $item['title'] }}</dd></div>
                                <div><dt>Valoración</dt><dd>{{ $item['score'] }}</dd></div>
                                <div><dt>Empleado</dt><dd>{{ $item['employee'] }}</dd></div>
                            </dl>
                        @else
                            @if($item['body'])
                                <p>{{ $item['body'] }}</p>
                            @endif
                            <p class="alert-line"><span>Fecha:</span> {{ $item['date'] }}</p>
                            @if($item['link'])
                                <a class="alert-google-btn" href="{{ $item['link'] }}" target="_blank" rel="noopener">Ver últimas reseñas en Google <span aria-hidden="true">→</span></a>
                            @endif
                        @endif
                    </div>
                </details>
            @endforeach
        @endif
    </main>
@endsection

@push('scripts')
<script>
function markAlertRead(item) {
    if (item.dataset.sent === '1') return;
    var wasUnread = item.classList.contains('is-unread');
    item.dataset.sent = '1';
    item.classList.remove('is-unread');
    var dot = item.querySelector('.dot');
    if (dot) dot.remove();
    if (wasUnread) {
        var total = document.getElementById('alert-unread-total');
        var count = Math.max(0, parseInt(total.dataset.count, 10) - 1);
        total.dataset.count = String(count);
        total.textContent = count + (count === 1 ? ' alerta sin leer' : ' alertas sin leer');
        var sub = document.getElementById(item.dataset.tone === 'google' ? 'alert-unread-google' : 'alert-unread-internal');
        if (sub) sub.textContent = String(Math.max(0, parseInt(sub.textContent, 10) - 1));
    }
    if (!item.dataset.read) return;
    fetch(item.dataset.read, {
        method: 'POST',
        keepalive: true,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        }
    }).catch(function () {});
}

document.querySelectorAll('.filters a').forEach(function (link) {
    link.addEventListener('click', function () {
        document.querySelectorAll('details.alert[open]').forEach(function (item) {
            item.open = false;
        });
    });
});

document.querySelectorAll('details.alert').forEach(function (item) {
    item.addEventListener('toggle', function () {
        if (!item.open) {
            markAlertRead(item);
            return;
        }
        document.querySelectorAll('details.alert').forEach(function (other) {
            if (other !== item && other.open) other.open = false;
        });
    });
});
</script>
@endpush
