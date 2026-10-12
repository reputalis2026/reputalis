@extends('pulse.layout')

@section('content')
    <main class="screen">
        <p class="sub">Mejor valoración primero</p>
        @if($team['ranked'] === [] && $team['unranked'] === [])
            <section class="card">@include('pulse.partials.empty', ['message' => 'Sin empleados activos'])</section>
        @else
            <section class="card" style="padding: 4px 8px 8px">
                <table class="table">
                    <thead>
                        <tr>
                            <th></th>
                            <th>Empleado</th>
                            <th class="num">Nota</th>
                            <th class="num">Encuestas</th>
                            <th class="num">Incidencias</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($team['ranked'] as $row)
                            <tr>
                                <td class="rank">{{ $row['position'] }}</td>
                                <td><a class="row-link" href="{{ url('/pulse/'.$client->code.'/equipo/'.$row['id']) }}">{{ $row['name'] }}</a></td>
                                <td class="num">{{ $row['average_label'] }}</td>
                                <td class="num">{{ \App\Support\Pulse\PulseFormat::integer($row['surveys']) }}</td>
                                <td class="num">{{ \App\Support\Pulse\PulseFormat::integer($row['incidents']) }}</td>
                                <td class="chev">›</td>
                            </tr>
                        @endforeach
                        @foreach($team['unranked'] as $row)
                            <tr>
                                <td class="rank">—</td>
                                <td>
                                    <a class="row-link" href="{{ url('/pulse/'.$client->code.'/equipo/'.$row['id']) }}">{{ $row['name'] }}</a>
                                    <span class="pill">Muestra insuficiente</span>
                                </td>
                                <td class="num">{{ $row['average_label'] ?? '—' }}</td>
                                <td class="num">{{ \App\Support\Pulse\PulseFormat::integer($row['surveys']) }}</td>
                                <td class="num">{{ \App\Support\Pulse\PulseFormat::integer($row['incidents']) }}</td>
                                <td class="chev">›</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </section>
        @endif
    </main>
@endsection