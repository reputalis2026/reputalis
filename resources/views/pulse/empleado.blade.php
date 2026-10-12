@extends('pulse.layout')

@section('content')
    <main class="screen stack empleado">
        <p class="place">
            @if($employee['position'])
                {{ \App\Support\Pulse\PulseFormat::ordinal($employee['position']) }} / {{ $employee['ranked_total'] }} · Clasificación acumulada
            @else
                Clasificación acumulada
            @endif
        </p>
        @include('pulse.partials.period', ['period' => $period])
        @if($employee['summary']['empty'])
            <section class="card">@include('pulse.partials.empty', ['message' => 'Sin encuestas en este periodo'])</section>
        @else
            <section class="kpis">
                <div class="kpi kpi-accent">
                    <span>Valoración</span>
                    <strong>{{ $employee['summary']['average_label'] }}</strong>
                </div>
                <div class="kpi">
                    <span>Encuestas</span>
                    <strong>{{ \App\Support\Pulse\PulseFormat::integer($employee['summary']['total']) }}</strong>
                </div>
                <div class="kpi">
                    <span>Positivas · 4 y 5</span>
                    <strong>{{ $employee['summary']['positive_label'] }}</strong>
                </div>
                <div class="kpi">
                    <span>Incidencias</span>
                    <strong>{{ \App\Support\Pulse\PulseFormat::integer($employee['summary']['incidents']) }}</strong>
                </div>
            </section>
            <section class="card chart-card">
                <h3>Distribución de puntuaciones</h3>
                @include('pulse.partials.distribution', ['bars' => $employee['summary']['distribution'], 'showCounts' => true, 'plot' => true, 'showZero' => true])
            </section>
        @endif
        <section class="card chart-card">
            <h3>Evolución de la satisfacción</h3>
            <p class="sub">Últimos 6 meses</p>
            @php
                $points = [];
                foreach ($employee['months'] as $month) {
                    $points[] = [
                        'label' => $month['label'],
                        'value' => $month['average'],
                        'text' => \App\Support\Pulse\PulseFormat::decimal($month['average'], 2) ?? '',
                    ];
                }
            @endphp
            @include('pulse.partials.vbars', ['points' => $points, 'max' => 5, 'caption' => 'Evolución de la satisfacción', 'plot' => true])
        </section>
        <section class="card chart-card">
            <h3>Crecimiento acumulado de encuestas</h3>
            <p class="sub">Últimos 6 meses</p>
            @include('pulse.partials.lines', ['area' => true, 'chart' => [
                'labels' => array_column($employee['months'], 'label'),
                'series' => [[
                    'name' => 'Encuestas',
                    'color' => '#1a9aaa',
                    'values' => array_map(fn ($month) => $month['total'], $employee['months']),
                ]],
            ]])
        </section>
        <section class="card chart-card points">
            <h3>Incidencias · puntos críticos</h3>
            <p class="sub">Últimos 6 meses</p>
            @if($employee['points'] === [])
                @include('pulse.partials.empty', ['message' => 'Sin puntos de mejora'])
            @else
                <table class="table">
                    <thead>
                        <tr>
                            <th>Punto de mejora</th>
                            <th class="num">Veces</th>
                            <th class="num">%</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($employee['points'] as $point)
                            <tr>
                                <td><i class="dot" style="background: {{ $point['color'] }}"></i>{{ $point['label'] }}</td>
                                <td class="num">{{ \App\Support\Pulse\PulseFormat::integer($point['times']) }}</td>
                                <td class="num">{{ $point['share_label'] }}</td>
                            </tr>
                        @endforeach
                        <tr class="is-total">
                            <td>Total</td>
                            <td class="num">{{ \App\Support\Pulse\PulseFormat::integer(array_sum(array_column($employee['points'], 'times'))) }}</td>
                            <td class="num">100 %</td>
                        </tr>
                    </tbody>
                </table>
            @endif
        </section>
    </main>
@endsection
