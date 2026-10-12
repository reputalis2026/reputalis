@extends('pulse.layout')

@section('content')
    <main class="screen">
        <div class="notice notice-info">
            <strong>0 notificaciones sin leer</strong>
            <span class="muted">0 internas · 0 Google</span>
        </div>
        <nav class="filters">
            <a class="btn is-on" href="{{ request()->url() }}?filtro=todas">Todas</a>
            <a class="btn btn-ghost" href="{{ request()->url() }}?filtro=sin_leer">Sin leer</a>
        </nav>
        <section class="card">@include('pulse.partials.empty', ['message' => 'No hay notificaciones'])</section>
    </main>
@endsection
