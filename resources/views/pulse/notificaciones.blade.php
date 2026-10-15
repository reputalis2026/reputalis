@extends('pulse.layout')

@section('content')
    <main class="screen" data-read-url="{{ $readUrl }}">
        <div class="notice notice-notes">
            <strong id="note-unread-total" data-count="{{ $notes['unread'] }}">{{ \App\Support\Pulse\PulseFormat::integer($notes['unread']) }} {{ $notes['unread'] === 1 ? 'notificación sin leer' : 'notificaciones sin leer' }}</strong>
            <span class="muted">
                <span id="note-unread-internal" data-count="{{ $notes['internal_unread'] }}">{{ $notes['internal_unread'] }} {{ $notes['internal_unread'] === 1 ? 'interna' : 'internas' }}</span>
                ·
                <span id="note-unread-google" data-count="{{ $notes['google_unread'] }}">{{ $notes['google_unread'] }} Google</span>
            </span>
        </div>
        <nav class="filters">
            <a class="btn {{ $filter === 'todas' ? 'is-on' : 'btn-ghost' }}" href="{{ request()->url() }}?filtro=todas">Todas</a>
            <a class="btn {{ $filter === 'sin_leer' ? 'is-on' : 'btn-ghost' }}" href="{{ request()->url() }}?filtro=sin_leer">Sin leer</a>
        </nav>
        @if($notes['items'] === [])
            <section class="card">@include('pulse.partials.empty', ['message' => $filter === 'sin_leer' ? 'No hay notificaciones sin leer' : 'No hay notificaciones'])</section>
        @else
            @foreach($notes['items'] as $item)
                <article class="note {{ $item['unread'] ? 'is-unread' : '' }}" data-id="{{ $item['id'] }}" data-tone="{{ $item['tone'] }}">
                    <span class="note-mark">
                        @if($item['unread'])
                            <span class="note-dot"></span>
                        @else
                            <svg class="note-check" width="18" height="18" viewBox="0 0 18 18" aria-hidden="true"><circle cx="9" cy="9" r="9" fill="#3cba8b"/><path d="M5 9.2l2.4 2.4L13 6.2" fill="none" stroke="#fff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        @endif
                    </span>
                    <div class="note-body">
                        <div class="note-kicker">
                            <span class="tag tag-{{ $item['tone'] }}">{{ $item['tone'] === 'google' ? 'GOOGLE' : 'INTERNA' }}</span>
                            <span class="date">{{ $item['date'] }}</span>
                        </div>
                        <p>{{ $item['text'] }}</p>
                    </div>
                </article>
            @endforeach
        @endif
    </main>
@endsection

@push('scripts')
<script>
(function () {
    var root = document.querySelector('main.screen');
    var readUrl = root ? root.dataset.readUrl : '';
    var pending = [];
    var timers = new Map();
    var check = '<svg class="note-check" width="18" height="18" viewBox="0 0 18 18" aria-hidden="true"><circle cx="9" cy="9" r="9" fill="#3cba8b"/><path d="M5 9.2l2.4 2.4L13 6.2" fill="none" stroke="#fff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>';

    function paintTotal() {
        var total = document.getElementById('note-unread-total');
        var count = Math.max(0, parseInt(total.dataset.count, 10) || 0);
        total.textContent = count + (count === 1 ? ' notificación sin leer' : ' notificaciones sin leer');
    }

    function paintPart(id, singular) {
        var el = document.getElementById(id);
        var count = Math.max(0, parseInt(el.dataset.count, 10) || 0);
        el.textContent = count + ' ' + (id === 'note-unread-internal' && count !== 1 ? 'internas' : singular);
    }

    function mark(card) {
        if (!card.classList.contains('is-unread') || card.dataset.sent === '1') return;
        card.dataset.sent = '1';
        card.classList.remove('is-unread');
        var mark = card.querySelector('.note-mark');
        if (mark) mark.innerHTML = check;
        var total = document.getElementById('note-unread-total');
        total.dataset.count = String(Math.max(0, parseInt(total.dataset.count, 10) - 1));
        paintTotal();
        var part = document.getElementById(card.dataset.tone === 'google' ? 'note-unread-google' : 'note-unread-internal');
        part.dataset.count = String(Math.max(0, parseInt(part.dataset.count, 10) - 1));
        paintPart('note-unread-internal', 'interna');
        paintPart('note-unread-google', 'Google');
        pending.push(card.dataset.id);
    }

    function flush() {
        if (!pending.length || !readUrl) return Promise.resolve();
        var ids = pending.splice(0, pending.length);
        return fetch(readUrl, {
            method: 'POST',
            keepalive: true,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ ids: ids })
        }).catch(function () {});
    }

    if ('IntersectionObserver' in window) {
        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                var card = entry.target;
                if (entry.isIntersecting && entry.intersectionRatio >= 0.6) {
                    if (timers.has(card)) return;
                    timers.set(card, setTimeout(function () {
                        timers.delete(card);
                        mark(card);
                    }, 2000));
                    return;
                }
                if (timers.has(card)) {
                    clearTimeout(timers.get(card));
                    timers.delete(card);
                }
            });
        }, { threshold: [0.6] });
        document.querySelectorAll('.note.is-unread').forEach(function (card) {
            observer.observe(card);
        });
    }

    document.addEventListener('click', function (event) {
        var link = event.target.closest('a');
        if (!link || !pending.length || link.target === '_blank') return;
        event.preventDefault();
        var href = link.href;
        flush().finally(function () { window.location = href; });
    }, true);

    window.addEventListener('pagehide', function () { flush(); });
    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'hidden') flush();
    });
})();
</script>
@endpush
