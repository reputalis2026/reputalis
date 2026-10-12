@extends('pulse.layout')

@section('content')
    <main class="screen">
        <p class="sub">Competidores de tu código postal{{ $client->codigo_postal ? ' · '.$client->codigo_postal : '' }}</p>
        <section class="card">
            @include('pulse.partials.empty', ['message' => 'Sin ranking de tu código postal'])
            <a class="btn btn-block" href="{{ url('/pulse/'.$client->code.'/sector/lista') }}">Ver lista completa</a>
        </section>
    </main>
@endsection
