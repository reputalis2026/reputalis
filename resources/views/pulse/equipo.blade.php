@extends('pulse.layout')

@section('content')
    <main class="screen equipo">
        <p class="sub">Mejor valoración primero</p>
        @if($team['ranked'] === [] && $team['unranked'] === [])
            <section class="card">@include('pulse.partials.empty', ['message' => 'Sin empleados activos'])</section>
        @else
            <section class="card">
                <table class="table">
                    <colgroup>
                        <col class="c-rank">
                        <col class="c-name">
                        <col class="c-nota">
                        <col class="c-enc">
                        <col class="c-inc">
                        <col class="c-chev">
                    </colgroup>
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
                                <td class="rank is-place">{{ $row['position'] }}</td>
                                <td class="who"><a class="row-link" href="{{ url('/pulse/'.$client->code.'/equipo/'.$row['id']) }}">{{ $row['name'] }}</a></td>
                                <td class="num">{{ $row['average_label'] ?? '—' }}</td>
                                <td class="num">{{ \App\Support\Pulse\PulseFormat::integer($row['surveys']) }}</td>
                                <td class="num">{{ \App\Support\Pulse\PulseFormat::integer($row['incidents']) }}</td>
                                <td class="chev" aria-hidden="true">›</td>
                            </tr>
                        @endforeach
                        @foreach($team['unranked'] as $row)
                            <tr>
                                <td class="rank">—</td>
                                <td class="who"><a class="row-link" href="{{ url('/pulse/'.$client->code.'/equipo/'.$row['id']) }}">{{ $row['name'] }}</a></td>
                                <td class="num">{{ $row['average_label'] ?? '—' }}</td>
                                <td class="num">{{ \App\Support\Pulse\PulseFormat::integer($row['surveys']) }}</td>
                                <td class="num">{{ \App\Support\Pulse\PulseFormat::integer($row['incidents']) }}</td>
                                <td class="chev" aria-hidden="true">›</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </section>
        @endif
    </main>
@endsection