@extends('pulse.layout')

@section('content')
    <main class="screen stack">
        <p class="sub">Detalle del punto de mejora</p>
        @if($detail['empty'])
            <section class="card">@include('pulse.partials.empty', ['message' => 'Sin encuestas en este periodo'])</section>
        @else
            <section class="kpis" style="grid-template-columns: repeat(3, 1fr)">
                <div class="kpi" style="background:#f8f1df">
                    <span>Incidencias</span>
                    <strong>{{ \App\Support\Pulse\PulseFormat::integer($detail['times']) }}</strong>
                </div>
                <div class="kpi">
                    <span>Encuestas analizadas</span>
                    <strong>{{ \App\Support\Pulse\PulseFormat::integer($detail['surveys']) }}</strong>
                </div>
                <div class="kpi">
                    <span>Lo señalan</span>
                    <strong style="font-size:22px">{{ $detail['share_label'] ?? '—' }}</strong>
                </div>
            </section>
            @if($detail['trend'])
                <div class="banner">
                    @if($detail['trend'] === 'favorable')
                        Evolución favorable
                    @elseif($detail['trend'] === 'desfavorable')
                        Evolución desfavorable
                    @else
                        Sin cambio
                    @endif
                    @if($detail['from_label'] && $detail['to_label'])
                        <span style="font-weight:500"> {{ $detail['from_label'] }} → {{ $detail['to_label'] }}</span>
                    @endif
                </div>
            @endif
            <section class="card chart-card">
                <h3>Evolución de {{ $detail['label'] }}</h3>
                <p class="sub">% de encuestas que señalan este punto<br>Últimos 6 meses</p>
                @php
                    $points = [];
                    $max = 1;
                    foreach ($detail['months'] as $month) {
                        $points[] = [
                            'label' => $month['label'],
                            'value' => $month['rate'],
                            'text' => $month['label_rate'] ?? '',
                        ];
                        if ($month['rate'] !== null && $month['rate'] > $max) {
                            $max = $month['rate'];
                        }
                    }
                @endphp
                @include('pulse.partials.vbars', ['points' => $points, 'max' => $max, 'tone' => 'is-sand', 'lastTone' => 'is-sand-last'])
            </section>
            <section class="card">
                <h3>Incidencias por empleado</h3>
                <p class="sub">Número de veces · últimos 6 meses</p>
                @if($detail['by_employee']['rows'] === [])
                    @include('pulse.partials.empty', ['message' => 'Sin incidencias de este punto'])
                @else
                    <div style="overflow-x:auto">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Empleado</th>
                                    @foreach($detail['by_employee']['labels'] as $label)
                                        <th class="num">{{ $label }}</th>
                                    @endforeach
                                    <th class="num">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($detail['by_employee']['rows'] as $row)
                                    <tr>
                                        <td>{{ $row['name'] }}</td>
                                        @foreach($row['cells'] as $cell)
                                            <td class="num">
                                                @if($cell > 0)
                                                    <span class="heat {{ $cell > 1 ? 'is-hot' : '' }}">{{ $cell }}</span>
                                                @else
                                                    —
                                                @endif
                                            </td>
                                        @endforeach
                                        <td class="num">{{ $row['total'] }}<br><span class="muted">{{ $row['share_label'] }}</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>
        @endif
    </main>
@endsection
