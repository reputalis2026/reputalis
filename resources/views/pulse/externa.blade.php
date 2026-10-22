@extends('pulse.layout')

@section('content')
    <main class="screen stack externa">
        @include('pulse.partials.period', ['period' => $period])
        @if($summary === null)
            <section class="card">
                @include('pulse.partials.empty', [
                    'message' => $period === 'acumulado' ? 'Sin datos de Google' : 'Sin datos de este periodo',
                    'retry' => $error !== null,
                ])
            </section>
        @else
            @if($summary['updated_label'])
                <p class="muted">Google Maps · {{ $summary['updated_label'] }}</p>
            @endif
            <section class="kpis">
                <div class="kpi kpi-accent">
                    <span>Nota Google</span>
                    <strong>{{ $summary['google_label'] ?? '—' }}</strong>
                </div>
                <div class="kpi">
                    <span>Reseñas</span>
                    <strong>{{ \App\Support\Pulse\PulseFormat::integer($summary['reviews']) }}</strong>
                </div>
            </section>
            <section class="card chart-card">
                <h3>Distribución de valoraciones</h3>
                @include('pulse.partials.distribution', ['bars' => $summary['distribution'], 'showCounts' => true, 'plot' => true, 'showStars' => true])
            </section>
            <section class="card goal-card">
                <div class="goal-head">
                    <h3>Objetivo nota Google</h3>
                    @if($summary['target_label'])
                        <div class="goal-pill">
                            <span>Objetivo</span>
                            <strong>{{ $summary['target_label'] }}</strong>
                        </div>
                    @endif
                </div>
                <div class="goal-row">
                    @php
                        $radius = 46;
                        $circ = 2 * pi() * $radius;
                        $offset = $circ * (1 - ($summary['progress'] / 100));
                    @endphp
                    <svg class="ring" viewBox="0 0 120 120" aria-label="{{ $summary['progress'] }}% de progreso">
                        <circle cx="60" cy="60" r="{{ $radius }}" fill="none" stroke="#d7eeee" stroke-width="12"/>
                        <circle cx="60" cy="60" r="{{ $radius }}" fill="none" stroke="#0e7f88" stroke-width="12"
                            stroke-linecap="round" stroke-dasharray="{{ round($circ, 2) }}" stroke-dashoffset="{{ round($offset, 2) }}"
                            transform="rotate(-90 60 60)"/>
                        <text x="60" y="58" text-anchor="middle" font-size="18" font-weight="700" fill="#1c2b33">{{ $summary['progress'] }}%</text>
                        <text x="60" y="74" text-anchor="middle" font-size="11" fill="#7d9198">Progreso</text>
                    </svg>
                    <div class="goal-copy">
                        <span class="muted">Nota real</span>
                        <b class="big">{{ $summary['real_label'] ?? '—' }}</b>
                        @if($summary['target_label'] && $summary['from_label'])
                            <p class="muted goal-note">Avance hacia la<br>nota Google {{ $summary['target_label'] }}<br>desde la nota {{ $summary['from_label'] }}.</p>
                        @endif
                    </div>
                </div>
                @if($summary['needed'] !== null)
                    <div class="banner">
                        @if((int) $summary['needed'] === 0)
                            El objetivo ya está alcanzado
                        @else
                            <span class="is-blue">Con {{ \App\Support\Pulse\PulseFormat::integer((int) $summary['needed']) }} reseñas de 5★</span>
                            puedes alcanzar el objetivo
                        @endif
                    </div>
                @endif
            </section>
        @endif

        @php
            $realPoints = [];
            $googlePoints = [];
            $realMax = 5;
            $googleMax = 5;
            foreach ($months as $month) {
                $realPoints[] = [
                    'label' => $month['label'],
                    'value' => $month['real'],
                    'text' => \App\Support\Pulse\PulseFormat::decimal($month['real'], 2) ?? '',
                ];
                $googlePoints[] = [
                    'label' => $month['label'],
                    'value' => $month['google'],
                    'text' => \App\Support\Pulse\PulseFormat::decimal($month['google'], 1) ?? '',
                ];
            }
            $hasMonths = collect($months)->contains(fn ($month) => $month['reviews'] !== null);
        @endphp
        @if($hasMonths)
            <section class="card chart-card">
                <h3>Evolución de la nota real</h3>
                <p class="sub">Últimos 6 meses</p>
                @include('pulse.partials.vbars', ['points' => $realPoints, 'max' => $realMax, 'tone' => 'is-lilac', 'lastTone' => 'is-lilac-last', 'plot' => true])
            </section>
            <section class="card chart-card">
                <h3>Evolución de la nota Google</h3>
                <p class="sub">Últimos 6 meses</p>
                @include('pulse.partials.vbars', ['points' => $googlePoints, 'max' => $googleMax, 'plot' => true])
            </section>
            <section class="card chart-card">
                <h3>Crecimiento acumulado de reseñas</h3>
                <p class="sub">Últimos 6 meses</p>
                @include('pulse.partials.lines', ['area' => true, 'zoom' => true, 'chart' => [
                    'labels' => array_column($months, 'label'),
                    'series' => [[
                        'name' => 'Reseñas',
                        'color' => '#1a9aaa',
                        'values' => array_map(fn ($month) => $month['reviews'], $months),
                    ]],
                ]])
            </section>
        @endif
    </main>
@endsection
