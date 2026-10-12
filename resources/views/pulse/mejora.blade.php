@extends('pulse.layout')

@section('content')
    <main class="screen stack mejora">
        <p class="lead">Detalle del punto de mejora</p>
        @if($detail['empty'])
            <section class="card">@include('pulse.partials.empty', ['message' => 'Sin encuestas en este periodo'])</section>
        @else
            <section class="kpis">
                <div class="kpi is-sand">
                    <span>Incidencias</span>
                    <strong>{{ \App\Support\Pulse\PulseFormat::integer($detail['times']) }}</strong>
                </div>
                <div class="kpi">
                    <span>Encuestas analizadas</span>
                    <strong>{{ \App\Support\Pulse\PulseFormat::integer($detail['surveys']) }}</strong>
                </div>
                <div class="kpi">
                    <span>Lo señalan</span>
                    <strong>{{ $detail['share_label'] ?? '—' }}</strong>
                </div>
            </section>
            @if($detail['trend'])
                <div class="trend {{ $detail['trend'] === 'desfavorable' ? 'is-down' : ($detail['trend'] === 'estable' ? 'is-flat' : '') }}">
                    <strong>
                        @if($detail['trend'] === 'favorable')
                            Evolución favorable
                        @elseif($detail['trend'] === 'desfavorable')
                            Evolución desfavorable
                        @else
                            Sin cambio
                        @endif
                    </strong>
                    @if($detail['from_label'] && $detail['to_label'])
                        <span>{{ $detail['from_label'] }} → {{ $detail['to_label'] }}</span>
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
            <section class="card staff">
                <h3>Incidencias por empleado</h3>
                <p class="sub">Número de veces · últimos 6 meses</p>
                @if($detail['by_employee']['rows'] === [])
                    @include('pulse.partials.empty', ['message' => 'Sin incidencias de este punto'])
                @else
                    <div class="staff-scroll">
                        <table class="table">
                            <colgroup>
                                <col class="c-name">
                                @foreach($detail['by_employee']['labels'] as $label)
                                    <col>
                                @endforeach
                                <col class="c-total">
                            </colgroup>
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
                                    @php
                                        $name = preg_split('/\s+/u', trim((string) $row['name']), 2) ?: [''];
                                    @endphp
                                    <tr>
                                        <td class="who"><span>{{ $name[0] }}</span>@if(($name[1] ?? '') !== '')<span>{{ $name[1] }}</span>@endif</td>
                                        @foreach($row['cells'] as $cell)
                                            <td class="num">
                                                @if($cell > 0)
                                                    <span class="heat {{ $cell > 1 ? 'is-hot' : '' }}">{{ $cell }}</span>
                                                @else
                                                    <span class="dash">—</span>
                                                @endif
                                            </td>
                                        @endforeach
                                        <td class="num">{{ \App\Support\Pulse\PulseFormat::integer($row['total']) }}@if($row['share_label'])<span class="share">{{ $row['share_label'] }}</span>@endif</td>
                                    </tr>
                                @endforeach
                                <tr class="is-total">
                                    <td>Total equipo</td>
                                    @foreach($detail['by_employee']['totals'] as $total)
                                        <td class="num">
                                            @if($total > 0)
                                                {{ \App\Support\Pulse\PulseFormat::integer($total) }}
                                            @else
                                                <span class="dash">—</span>
                                            @endif
                                        </td>
                                    @endforeach
                                    <td class="num">{{ \App\Support\Pulse\PulseFormat::integer($detail['by_employee']['total']) }}@if($detail['by_employee']['share_label'])<span class="share">{{ $detail['by_employee']['share_label'] }}</span>@endif</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>
        @endif
    </main>
@endsection
