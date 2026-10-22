@extends('pulse.layout')

@section('content')
    <main class="login-screen">
        <div id="pulse-install-help" class="install-help hidden" hidden></div>
        <img class="wordmark" src="{{ asset('img/pulse-wordmark.png') }}" alt="Reputalis">
        <form class="login-card" method="POST" action="{{ url('/pulse/login') }}">
            @csrf
            @if(isset($errors) && $errors->any())
                <div class="form-error">{{ $errors->first() }}</div>
            @endif
            <label for="email">Usuario o email</label>
            <input type="text" name="email" id="email" value="{{ old('email') }}" required autofocus autocomplete="username" placeholder="Usuario o email">
            <label for="password">Contraseña</label>
            <input type="password" name="password" id="password" required autocomplete="current-password" placeholder="Contraseña">
            <button type="submit" class="btn btn-block">Entrar</button>
        </form>
    </main>
@endsection

@push('scripts')
<script>
(function () {
    var params = new URLSearchParams(window.location.search);
    if (params.get('instalar') !== '1') return;
    var help = document.getElementById('pulse-install-help');
    if (!help) return;
    help.hidden = false;
    help.textContent = 'Para poner el icono en el iPhone: pulsa Compartir y luego «Añadir a pantalla de inicio». Al abrirlo, inicia sesión en esta pantalla.';
})();
</script>
@endpush
