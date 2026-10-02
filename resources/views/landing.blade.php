<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0f766e">
    <title>Reputalis</title>
    @include('pulse-pwa-head')
    <style>
        * { box-sizing: border-box; }
        html, body { height: 100%; margin: 0; }
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f4f7f6;
            color: #12353c;
            font-family: system-ui, sans-serif;
        }
        main {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 2rem;
            padding: 1.5rem;
            text-align: center;
            max-width: 28rem;
        }
        img {
            width: min(16rem, 80vw);
            height: auto;
        }
        button {
            display: inline-block;
            padding: .9rem 1.4rem;
            border: 0;
            border-radius: 999px;
            background: #06232b;
            color: #fff;
            font-size: 1rem;
            font-weight: 650;
            cursor: pointer;
        }
        button:hover { background: #0b3a46; }
        #install-help {
            margin: 0;
            font-size: .95rem;
            line-height: 1.45;
            color: #12353c;
        }
        #install-help[hidden] { display: none; }
    </style>
</head>
<body>
    <main>
        <img src="{{ asset('img/logoReputalis.png') }}" alt="Reputalis" width="280" height="44">
        <button type="button" id="install-pulse">Descargar El Pulso del Día</button>
        <p id="install-help" hidden></p>
    </main>
    <script>
    (function () {
        var deferredPrompt = null;
        var button = document.getElementById('install-pulse');
        var help = document.getElementById('install-help');
        var loginUrl = @json(url('/pulse'));

        window.addEventListener('beforeinstallprompt', function (event) {
            event.preventDefault();
            deferredPrompt = event;
        });

        function isIos() {
            var ua = navigator.userAgent || '';
            return /iphone|ipad|ipod/i.test(ua) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
        }

        function isStandalone() {
            return window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
        }

        function showHelp(text) {
            help.hidden = false;
            help.textContent = text;
        }

        var waitingForInstall = false;

        function promptInstall() {
            var promptEvent = deferredPrompt;
            deferredPrompt = null;
            promptEvent.prompt();
            promptEvent.userChoice.then(function (choice) {
                if (choice.outcome === 'accepted') {
                    showHelp('Listo. El icono de El Pulso del Día queda en la pantalla de inicio. Ábrelo para iniciar sesión.');
                }
            });
        }

        button.addEventListener('click', function () {
            if (waitingForInstall) return;

            if (isStandalone()) {
                window.location.href = loginUrl;
                return;
            }

            if (isIos()) {
                window.location.href = loginUrl + '?instalar=1';
                return;
            }

            if (deferredPrompt) {
                promptInstall();
                return;
            }

            waitingForInstall = true;
            var started = Date.now();
            function waitForPrompt() {
                if (deferredPrompt) {
                    waitingForInstall = false;
                    promptInstall();
                    return;
                }
                if (Date.now() - started < 3000) {
                    setTimeout(waitForPrompt, 200);
                    return;
                }
                waitingForInstall = false;
                showHelp('Si no aparece el aviso de instalación, abre el menú del navegador y elige «Instalar aplicación» o «Añadir a pantalla de inicio». El icono abre el inicio de sesión.');
            }
            waitForPrompt();
        });
    })();
    </script>
</body>
</html>
