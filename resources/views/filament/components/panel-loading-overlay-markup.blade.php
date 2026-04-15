{{-- Overlay global de carga (panel admin). z-40: por debajo de modales/backdrops típicos de Filament (z-50+). --}}
<div
    id="panel-overlay"
    class="fixed inset-0 z-40 hidden flex items-center justify-center bg-slate-900/40 backdrop-blur-sm opacity-0 pointer-events-none transition-opacity duration-300 ease-out motion-reduce:duration-75 motion-reduce:transition-opacity"
    aria-hidden="true"
    role="status"
>
    <div class="rounded-2xl bg-white px-8 py-6 shadow-2xl flex flex-col items-center gap-3 animate-pulse motion-reduce:animate-none">
        <svg
            class="h-10 w-10 animate-spin text-amber-500 motion-reduce:animate-none"
            fill="none"
            viewBox="0 0 24 24"
            xmlns="http://www.w3.org/2000/svg"
            aria-hidden="true"
        >
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path
                class="opacity-75"
                fill="currentColor"
                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
            ></path>
        </svg>
        <span class="text-slate-600 font-medium" id="panel-overlay-text">{{ __('Cargando...') }}</span>
    </div>
</div>
