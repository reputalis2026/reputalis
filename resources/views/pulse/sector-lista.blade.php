@extends('pulse.layout')

@php
    $fmt = \App\Support\Pulse\PulseFormat::class;
@endphp

@section('content')
    <main class="screen sector sector-list">
        <h2 class="block-title">Farmacias de tu código postal</h2>
        <p class="sub">
            @if($sector['postal_code'] !== '')
                {{ $sector['postal_code'] }} ·
            @endif
            Ordenadas por posición
        </p>
        <div class="list-meta">
            <span>{{ $sector['analyzed'] }} farmacias</span>
            <span>Tu posición: {{ $fmt::ordinal($sector['position'], true) }}</span>
        </div>
        <section class="card">
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
                    @foreach($sector['ranking'] as $row)
                        <tr class="{{ $row['is_you'] ? 'is-you' : '' }}">
                            <td>{{ $fmt::ordinal($row['position'], true) }}</td>
                            <td>{{ $row['name'] }}</td>
                            <td class="num">{{ $fmt::decimal($row['rating'], 1) }}</td>
                            <td class="num">{{ $fmt::integer($row['reviews']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>
    </main>
@endsection
