@php
    $defaultText = __('Cargando...');
@endphp
<script>
    (function () {
        'use strict';

        const defaultText = @json($defaultText);

        function fadeDurationMs() {
            if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                return 75;
            }
            return 350;
        }

        /**
         * @param {boolean} show
         * @param {string} [text]
         */
        window.setPanelOverlay = function setPanelOverlay(show, text = defaultText) {
            const ov = document.getElementById('panel-overlay');
            const txt = document.getElementById('panel-overlay-text');
            if (txt && text) {
                txt.textContent = text;
            }
            if (! ov) {
                return;
            }

            if (show) {
                ov.classList.remove('hidden');
                ov.setAttribute('aria-hidden', 'false');
                requestAnimationFrame(function () {
                    ov.classList.remove('opacity-0', 'pointer-events-none');
                    ov.classList.add('opacity-100');
                });
                return;
            }

            ov.classList.remove('opacity-100');
            ov.classList.add('opacity-0', 'pointer-events-none');

            const wait = fadeDurationMs();
            window.clearTimeout(window.__panelOverlayHideTimer);
            window.__panelOverlayHideTimer = window.setTimeout(function () {
                ov.classList.add('hidden');
                ov.setAttribute('aria-hidden', 'true');
            }, wait);
        };

        function hidePanelOverlayDeferred() {
            window.setPanelOverlay(false);
        }

        /** Navegación SPA (wire:navigate): evita ocultar antes de que termine el morph. */
        let navPending = false;

        document.addEventListener('livewire:navigating', function () {
            navPending = true;
            window.setPanelOverlay(true, defaultText);
        });

        document.addEventListener('livewire:navigated', function () {
            navPending = false;
            window.clearTimeout(window.__panelOverlayNavHideTimer);
            const delay = fadeDurationMs();
            window.__panelOverlayNavHideTimer = window.setTimeout(function () {
                hidePanelOverlayDeferred();
            }, Math.max(200, delay));
        });

        function registerLivewireRequestHook() {
            if (typeof Livewire === 'undefined' || typeof Livewire.hook !== 'function') {
                return false;
            }

            Livewire.hook('request', function ({ succeed, fail }) {
                const showTimer = window.setTimeout(function () {
                    if (! navPending) {
                        window.setPanelOverlay(true, defaultText);
                    }
                }, 120);

                const finish = function () {
                    window.clearTimeout(showTimer);
                    if (! navPending) {
                        hidePanelOverlayDeferred();
                    }
                };

                succeed(finish);
                fail(finish);
            });

            return true;
        }

        if (! registerLivewireRequestHook()) {
            document.addEventListener('livewire:init', function onLwInit() {
                if (registerLivewireRequestHook()) {
                    document.removeEventListener('livewire:init', onLwInit);
                }
            });
        }
    })();
</script>
