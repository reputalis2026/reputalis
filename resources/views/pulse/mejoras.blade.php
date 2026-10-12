@extends('pulse.layout')

@section('content')
    <main class="screen stack mejoras">
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
                    <strong>{{ \App\Support\Pulse\PulseFormat::integer($report['with_point']) }}@if($report['with_point_label'])<b class="pct"> · {{ $report['with_point_label'] }}</b>@endif</strong>
                </div>
            </section>
            <section class="card points">
                <h3>Prioridad de mejora</h3>
                <p class="sub">Ordenada por número de incidencias</p>
                @if($report['rows'] === [])
                    @include('pulse.partials.empty', ['message' => 'Sin puntos de mejora configurados'])
                @elseif($report['incidents'] === 0)
                    @include('pulse.partials.empty', ['message' => 'Sin puntos de mejora en este periodo'])
                @else
                    <table class="table">
                        <colgroup>
                            <col>
                            <col class="c-times">
                            <col class="c-share">
                            <col class="c-chev">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Punto de mejora</th>
                                <th class="num">N.º de<br>incidencias</th>
                                <th class="num">%</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($report['rows'] as $row)
                                <tr>
                                    <td class="who"><i class="dot" style="background: {{ $row['color'] }}"></i><a class="row-link" href="{{ url('/pulse/'.$client->code.'/mejoras/'.$row['id']) }}">{{ $row['label'] }}</a></td>
                                    <td class="num">{{ \App\Support\Pulse\PulseFormat::integer($row['times']) }}</td>
                                    <td class="num">{{ $row['share_label'] }}</td>
                                    <td class="chev" aria-hidden="true">›</td>
                                </tr>
                            @endforeach
                            <tr class="is-total">
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
                    @include('pulse.partials.lines', ['chart' => $report['series'], 'asPercent' => true, 'legendTop' => true, 'inspect' => true])
                    <p class="line-tip" role="status"></p>
                    <p class="line-hint">Toca un punto para consultar el valor exacto.</p>
                </section>
            @endif
        @endif
    </main>
@endsection

@push('scripts')
<script>
document.querySelectorAll('.line-hit').forEach(function (dot) {
    dot.addEventListener('click', function () {
        var tip = document.querySelector('.line-tip');
        if (!tip) return;
        tip.classList.add('is-on');
        tip.replaceChildren();
        var name = document.createElement('b');
        name.className = 'name';
        name.textContent = dot.dataset.name;
        var rest = document.createElement('span');
        rest.textContent = ' · ' + dot.dataset.when + ' · ' + dot.dataset.value;
        tip.append(name, rest);
    });
});
</script>
@endpush
