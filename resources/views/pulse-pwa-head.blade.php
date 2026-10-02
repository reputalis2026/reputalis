<link rel="manifest" href="{{ url('/pulse/manifest.webmanifest') }}">
<link rel="apple-touch-icon" href="{{ asset('img/pulse-icon-180.png') }}">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="Pulso">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<script>
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register(@json(url('/pulse/sw')), { scope: '/' }).then(function () {
        return navigator.serviceWorker.getRegistrations();
    }).then(function (regs) {
        var rootScope = location.origin + '/';
        regs.forEach(function (reg) {
            if (reg.scope !== rootScope && reg.scope.indexOf('/pulse/') !== -1) {
                reg.unregister();
            }
        });
    }).catch(function () {});
}
</script>
