@extends('pulse.layout')

@section('content')
    <main class="screen stack">
        @include('pulse.partials.period', ['period' => $period])
        @if($report['surveys'] === 0)
            <section class="card">@include('pulse.partials.empty', ['message' => 'Sin encuestas en este periodo'])</section>
        @else
            <section class="kpis">
                <div class="kpi">
                    <span>Encuestas del periodo</span>
                    <strong>{{ \App\Support\Pulse\PulseFormat::integer($report['surveys']) }}</strong>
                </div>
                <div class="kpi kpi-accent">
                    <span>Con punto de mejora</span>
                    <strong>{{ \App\Support\Pulse\PulseFormat::integer($report['with_point']) }}</strong>
                    <small>{{ $report['with_point_label'] }}</small>
                </div>
            </section>
            <section class="card">
                <h3>Prioridad de mejora</h3>
                <p class="sub">Ordenada por número de incidencias</p>
                @if($report['rows'] === [])
                    @include('pulse.partials.empty', ['message' => 'Sin puntos de mejora configurados'])
                @elseif($report['incidents'] === 0)
                    @include('pulse.partials.empty', ['message' => 'Sin puntos de mejora en este periodo'])
                @else
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Punto de mejora</th>
                                <th class="num">N.º de incidencias</th>
                                <th class="num">%</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($report['rows'] as $row)
                                <tr>
                                    <td><i class="dot" style="background: {{ $row['color'] }}"></i>{{ $row['label'] }}</td>
                                    <td class="num">{{ \App\Support\Pulse\PulseFormat::integer($row['times']) }}</td>
                                    <td class="num">{{ $row['share_label'] }}</td>
                                    <td><a class="chev" href="{{ url('/pulse/'.$client->code.'/mejoras/'.$row['id']) }}" aria-label="Detalle">›</a></td>
                                </tr>
                            @endforeach
                            <tr>
                                <td>Total</td>
                                <td class="num">{{ \App\Support\Pulse\PulseFormat::integer($report['incidents']) }}</td>
                                <td class="num">100 %</td>
                                <td></td>
                            </tr>
                        </tbody>
                    </table>
                @endif
            </section>
            @if($report['series']['series'] !== [])
                <section class="card chart-card">
                    <h3>Evolución de los puntos de mejora</h3>
                    <p class="sub">% de encuestas que señalan cada punto<br>Últimos 6 meses</p>
                    @include('pulse.partials.lines', ['chart' => $report['series'], 'asPercent' => true])
                </section>
            @endif
        @endif
    </main>
@endsection
