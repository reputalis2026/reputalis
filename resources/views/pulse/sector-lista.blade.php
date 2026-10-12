@extends('pulse.layout')

@section('content')
    <main class="screen">
        <h2 class="block-title">Farmacias de tu código postal</h2>
        <section class="card">
            @include('pulse.partials.empty', ['message' => 'Sin ranking de tu código postal'])
        </section>
    </main>
@endsection
