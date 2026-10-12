@extends('pulse.layout')

@section('content')
    <main class="splash">
        <img class="wordmark" src="{{ asset('img/pulse-wordmark.png') }}" alt="Reputalis">
        <span class="splash-rule"></span>
        <h1>{{ $client->namecommercial }}</h1>
        <p>Tu reputación se construye cada día.</p>
        <noscript><a href="{{ $refreshUrl }}">Continuar</a></noscript>
    </main>
@endsection

@push('scripts')
<script>
setTimeout(function () {
    try {
        if (localStorage.getItem('pulse_auth') !== '1') return;
    } catch (e) {
        return;
    }
    location.replace(@json($refreshUrl));
}, {{ (int) config('elpulso.splash_ms', 4800) }});
</script>
@endpush
