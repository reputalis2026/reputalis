@extends('pulse.layout')

@section('content')
    <main class="screen stack interna">
        @include('pulse.partials.period', ['period' => $period])
        @if($summary['empty'])
            <section class="card">@include('pulse.partials.empty', ['message' => 'Sin encuestas en este periodo'])</section>
        @else
            <section class="kpis">
                <div class="kpi kpi-accent">
                    <span>Valoración</span>
                    <strong>{{ $summary['average_label'] }}</strong>
                </div>
                <div class="kpi">
                    <span>Encuestas</span>
                    <strong>{{ \App\Support\Pulse\PulseFormat::integer($summary['total']) }}</strong>
                </div>
                <div class="kpi">
                    <span>Positivas · 4 y 5</span>
                    <strong>{{ $summary['positive_label'] }}</strong>
                </div>
            </section>
            <section class="card chart-card">
                <h3>Distribución de puntuaciones</h3>
                @include('pulse.partials.distribution', ['bars' => $summary['distribution'], 'showCounts' => true, 'plot' => true])
            </section>
        @endif
        <section class="card chart-card">
            <h3>Evolución de la satisfacción</h3>
            <p class="sub">Últimos 6 meses</p>
            @php
                $points = [];
                $max = 5;
                foreach ($months as $month) {
                    $points[] = [
                        'label' => $month['label'],
                        'value' => $month['average'],
                        'text' => \App\Support\Pulse\PulseFormat::decimal($month['average'], 2) ?? '',
                    ];
                }
            @endphp
            @include('pulse.partials.vbars', ['points' => $points, 'max' => $max, 'caption' => 'Evolución de la satisfacción', 'plot' => true])
        </section>
        <section class="card chart-card">
            <h3>Crecimiento acumulado de encuestas</h3>
            <p class="sub">Últimos 6 meses</p>
            @include('pulse.partials.lines', ['area' => true, 'chart' => [
                'labels' => array_column($months, 'label'),
                'series' => [[
                    'name' => 'Encuestas',
                    'color' => '#1a9aaa',
                    'values' => array_map(fn ($month) => $month['total'], $months),
                ]],
            ]])
        </section>
    </main>
@endsection
