@extends('pulse.layout')

@php
    $fmt = \App\Support\Pulse\PulseFormat::class;
@endphp

@section('content')
    <main class="screen stack sector">
        <p class="sub">
            Competidores de tu código postal
            @if($sector['postal_code'] !== '')
                · {{ $sector['postal_code'] }}
            @endif
        </p>

        <div class="sector-kpis">
            <div class="sector-kpi">
                <span>Tu posición</span>
                <strong><b>{{ $fmt::ordinal($sector['position']) }}</b> / {{ $sector['analyzed'] }}</strong>
            </div>
            <div class="sector-kpi">
                <span>Competidores analizados</span>
                <strong>{{ $fmt::integer($sector['analyzed']) }}</strong>
                <em>{{ $sector['ahead'] }} por delante · {{ $sector['behind'] }} por detrás</em>
            </div>
        </div>

        <p class="sector-banner">
            Con {{ $fmt::integer($sector['projection_stars']) }} reseñas de 5★ puedes alcanzar
            la <b>{{ $fmt::ordinal($sector['projection_position'], true) }}</b> posición
        </p>

        <section class="card sector-chart">
            <header>
                <h3>Farmacias por nota Google</h3>
                <span>{{ $sector['analyzed'] }} farmacias</span>
            </header>
            <div class="hist" role="img" aria-label="Farmacias por nota Google">
                <div class="hist-plot">
                    <span class="hist-avg" style="left: {{ $sector['average_left'] }}%">
                        <b>Media {{ $fmt::decimal($sector['average'], 2) }}</b>
                    </span>
                    <span class="hist-you" style="left: {{ $sector['you_left'] }}%">Tu farmacia · {{ $fmt::decimal($sector['your_rating'], 1) }}</span>
                    @foreach($sector['histogram'] as $bar)
                        <i class="{{ $bar['yours'] ? 'is-you' : '' }}" style="height: {{ $bar['height'] }}%"></i>
                    @endforeach
                </div>
                <div class="hist-axis">
                    <span>3,5</span>
                    <span>4,0</span>
                    <span>4,5</span>
                    <span>5,0</span>
                </div>
                <p>Nota Google</p>
            </div>
        </section>

        <section class="sector-near">
            <h3>Quién está cerca de tu posición</h3>
            <p class="sub">{{ $sector['ahead'] }} competidores por delante · {{ $sector['behind'] }} por detrás</p>
            <p class="sector-banner">Estás en la <b>{{ $fmt::ordinal($sector['position'], true) }}</b> posición de {{ $sector['analyzed'] }}</p>
            <div class="card">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Pos.</th>
                            <th>Farmacia</th>
                            <th class="num">Nota</th>
                            <th class="num">Reseñas</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($sector['nearby'] as $row)
                            <tr class="{{ $row['is_you'] ? 'is-you' : '' }}">
                                <td>{{ $fmt::ordinal($row['position'], true) }}</td>
                                <td>{{ $row['name'] }}</td>
                                <td class="num">{{ $fmt::decimal($row['rating'], 1) }}</td>
                                <td class="num">{{ $fmt::integer($row['reviews']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="sector-hint">A igual nota Google, queda primero quien tiene más reseñas.</p>
            <a class="sector-list-btn" href="{{ url('/pulse/'.$client->code.'/sector/lista') }}">
                Ver lista completa ({{ $sector['analyzed'] }})
                <span aria-hidden="true">›</span>
            </a>
        </section>

        <section class="card sector-evo-card">
            <h3>Evolución de tu posición</h3>
            <p class="sub">Últimos 6 meses</p>
            <div class="sector-evo">
                @foreach($sector['evolution'] as $point)
                    <div>
                        <b>{{ $fmt::ordinal($point['position'], true) }}</b>
                        <i class="{{ $loop->last ? 'is-last' : '' }}" style="height: {{ $point['height'] }}%"></i>
                        <em>{{ $point['label'] }}</em>
                    </div>
                @endforeach
            </div>
            <p class="sector-hint">Una posición menor indica una mejor clasificación.</p>
        </section>

        <section class="sector-zones">
            <h3>Tu posición territorial</h3>
            @foreach($sector['territories'] as $zone)
                <p class="zone-kind">{{ $zone['kind'] }}</p>
                <div class="card">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>{{ $zone['name'] }}</th>
                                <th class="num">Posición</th>
                                <th class="num">Delante</th>
                                <th class="num">Detrás</th>
                                <th class="num">Analizadas</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td></td>
                                <td class="num">{{ $fmt::ordinal($zone['position'], true) }}</td>
                                <td class="num">{{ $zone['ahead'] }}</td>
                                <td class="num">{{ $zone['behind'] }}</td>
                                <td class="num">{{ $zone['analyzed'] }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            @endforeach
        </section>
    </main>
@endsection
