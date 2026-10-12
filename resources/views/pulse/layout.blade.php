<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#e7f3ef" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#e7f3ef" media="(prefers-color-scheme: dark)">
    <meta name="color-scheme" content="light">
    <title>{{ $title ?? 'El Pulso del Día' }}</title>
    <style>
        html, body { background: #e7f3ef; color-scheme: light; }
        html:not(.pulse-ready) body { visibility: hidden; }
    </style>
    @include('pulse-pwa-head')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/pulse-app.css') }}?v={{ filemtime(public_path('css/pulse-app.css')) }}">
    <script>
    (function () {
        var mode = @json($pulseBoot ?? 'app');
        var key = 'pulse_auth';
        var params = new URLSearchParams(location.search);
        if ((mode === 'splash' || mode === 'launch') && params.get('entrada') === '1') {
            try { localStorage.setItem(key, '1'); } catch (e) {}
            params.delete('entrada');
            history.replaceState(null, '', location.pathname + (params.toString() ? '?' + params.toString() : '') + location.hash);
        }
        if (mode === 'login') {
            document.documentElement.classList.add('pulse-ready');
            return;
        }
        var hasAuth = false;
        try { hasAuth = localStorage.getItem(key) === '1'; } catch (e) {}
        if (!hasAuth) {
            location.replace(@json(url('/pulse/salir')));
            return;
        }
        function showLaunch() {
            try { sessionStorage.setItem('pulse_pass', '1'); } catch (e) {}
            document.documentElement.classList.add('pulse-ready', 'pulse-launching');
            clearTimeout(window.__pulseLaunchTimer);
            window.__pulseLaunchTimer = setTimeout(function () {
                document.documentElement.classList.remove('pulse-launching');
            }, 4800);
        }
        var pass = false;
        try { pass = sessionStorage.getItem('pulse_pass') === '1'; } catch (e) {}
        if (!pass) {
            showLaunch();
        } else {
            document.documentElement.classList.add('pulse-ready');
        }
        var firstShow = true;
        window.addEventListener('pageshow', function (event) {
            if (firstShow) {
                firstShow = false;
                return;
            }
            if (event.persisted) {
                document.documentElement.classList.remove('pulse-launching');
                document.documentElement.classList.add('pulse-ready');
            }
        });
    })();
    </script>
</head>
<body>
    @if(($pulseBoot ?? 'app') !== 'login')
        <div id="pulse-launch" class="splash">
            <img class="wordmark" src="{{ asset('img/pulse-wordmark.png') }}" alt="Reputalis">
            <span class="splash-rule"></span>
            @if(!empty($client))
                <h1>{{ $client->namecommercial }}</h1>
            @endif
            <p>Tu reputación se construye cada día.</p>
        </div>
    @endif
    <div class="pulse-app">
        <div class="offline" role="status">Sin conexión. Se muestra la última copia guardada.</div>
        @if(!empty($showChrome))
            <header class="topbar">
                <a class="icon-btn" href="{{ $backUrl }}" aria-label="Atrás">
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 6l-6 6 6 6"/></svg>
                </a>
                <h1>{{ $heading }}</h1>
                <a class="icon-btn" href="{{ $homeUrl }}" aria-label="Inicio">
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 11l8-7 8 7"/><path d="M6 10v9h12v-9"/></svg>
                </a>
            </header>
        @endif
        @yield('content')
    </div>
    <script>
    if (!navigator.onLine) document.body.classList.add('is-offline');
    window.addEventListener('offline', function () { document.body.classList.add('is-offline'); });
    window.addEventListener('online', function () { document.body.classList.remove('is-offline'); });
    </script>
    @stack('scripts')
</body>
</html>
