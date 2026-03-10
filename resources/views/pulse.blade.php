@php
    $clientCode = $client->code;
    $clientName = $client->namecommercial;
    $today = \App\Support\CsatMetrics::getMetrics($client->id, \App\Support\CsatMetrics::PERIOD_TODAY);
    $accumulated = \App\Support\CsatMetrics::getMetrics($client->id, \App\Support\CsatMetrics::PERIOD_ALL);
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0f766e">
    <title>El Pulso del Día — {{ $clientName }}</title>
    <link rel="manifest" href="{{ url("/pulse/{$clientCode}/manifest.json") }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .metric-card:active { opacity: 0.9; }
    </style>
</head>
<body class="min-h-screen bg-slate-100 text-slate-800 antialiased safe-area-pb">
    <div class="mx-auto max-w-md min-h-screen flex flex-col px-4 py-6" id="app">
        <header class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-lg font-semibold text-slate-800">El Pulso del Día</h1>
                <p class="text-sm text-slate-500">{{ $clientName }}</p>
            </div>
            <form method="POST" action="{{ url('/logout') }}" class="inline">
                @csrf
                <button type="submit" class="text-sm text-teal-600 font-medium">Salir</button>
            </form>
        </header>

        {{-- Bloque Interna: CSAT --}}
        <section class="mb-6">
            <h2 class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-3">Encuestas propias (CSAT)</h2>
            <div class="grid grid-cols-2 gap-3">
                <div class="metric-card rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200" data-metric="accumulated" tabindex="0" role="button">
                    <p class="text-xs text-slate-500 mb-1">Acumulado</p>
                    <p class="text-2xl font-bold text-slate-800">{{ $accumulated['avg_score'] !== null ? number_format($accumulated['avg_score'], 1, ',', '') : '—' }}</p>
                    <p class="text-sm text-slate-500">{{ $accumulated['total'] }} encuestas</p>
                    @if($accumulated['satisfied_pct'] !== null)
                        <p class="text-xs text-teal-600 mt-1">{{ $accumulated['satisfied_pct'] }}% satisfechos</p>
                    @endif
                </div>
                <div class="metric-card rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200" data-metric="today" tabindex="0" role="button">
                    <p class="text-xs text-slate-500 mb-1">Hoy</p>
                    <p class="text-2xl font-bold text-slate-800">{{ $today['avg_score'] !== null ? number_format($today['avg_score'], 1, ',', '') : '—' }}</p>
                    <p class="text-sm text-slate-500">{{ $today['today_count'] }} encuestas</p>
                </div>
            </div>
        </section>

        {{-- Bloque Externa: Google (placeholder) --}}
        <section class="mb-6">
            <h2 class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-3">Google (reseñas)</h2>
            <div class="rounded-2xl bg-slate-100 border border-slate-200 border-dashed p-6 text-center">
                <p class="text-slate-500 text-sm">Próximamente</p>
            </div>
        </section>

        {{-- Menú: Personal, Mejoras, Alertas --}}
        <nav class="mt-auto space-y-2">
            <button type="button" class="w-full rounded-xl bg-white px-4 py-3 text-left text-slate-700 shadow-sm ring-1 ring-slate-200 hover:bg-slate-50" data-menu="personal">Personal hoy</button>
            <button type="button" class="w-full rounded-xl bg-white px-4 py-3 text-left text-slate-700 shadow-sm ring-1 ring-slate-200 hover:bg-slate-50" data-menu="mejoras">Puntos de mejora</button>
            <button type="button" class="w-full rounded-xl bg-white px-4 py-3 text-left text-slate-700 shadow-sm ring-1 ring-slate-200 hover:bg-slate-50" data-menu="alertas">Alertas</button>
        </nav>
    </div>

    {{-- Modal gráfico semanal (tap en métricas) --}}
    <div id="modal-chart" class="fixed inset-0 z-20 hidden items-center justify-center bg-slate-900/50 p-4" aria-hidden="true">
        <div class="rounded-2xl bg-white w-full max-w-sm p-6 shadow-xl">
            <h3 class="font-semibold text-slate-800 mb-4" id="modal-chart-title">Gráfico semanal</h3>
            <div class="h-48 flex items-end justify-around gap-1 text-xs text-slate-500" id="modal-chart-bars">
                {{-- Placeholder: barras simples por día --}}
            </div>
            <button type="button" id="modal-chart-close" class="mt-4 w-full rounded-xl bg-slate-100 py-2 text-slate-600 font-medium">Cerrar</button>
        </div>
    </div>

    <script>
(function() {
    const clientCode = @json($clientCode);
    const apiUrl = @json(url("/api/pulse/" . $clientCode));

    document.querySelectorAll('.metric-card').forEach(function(card) {
        card.addEventListener('click', function() {
            const metric = this.dataset.metric;
            const modal = document.getElementById('modal-chart');
            const title = document.getElementById('modal-chart-title');
            const bars = document.getElementById('modal-chart-bars');
            title.textContent = metric === 'today' ? 'Hoy' : 'Acumulado — vista semanal';
            bars.innerHTML = '<p class="text-slate-400">Próximamente: gráfico semanal</p>';
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        });
    });

    document.getElementById('modal-chart-close').addEventListener('click', function() {
        const modal = document.getElementById('modal-chart');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    });

    document.getElementById('modal-chart').addEventListener('click', function(e) {
        if (e.target === this) {
            this.classList.add('hidden');
            this.classList.remove('flex');
        }
    });

    document.querySelectorAll('[data-menu]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const menu = this.dataset.menu;
            alert('Próximamente: ' + (menu === 'personal' ? 'Personal hoy' : menu === 'mejoras' ? 'Puntos de mejora' : 'Alertas'));
        });
    });

    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register(@json(url("/pulse/{$clientCode}/sw.js")), { scope: '/pulse/' + encodeURIComponent(clientCode) + '/' }).catch(function() {});
    }
})();
    </script>
</body>
</html>
