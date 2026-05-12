<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Encuesta no válida') }}</title>
    <style>
        body { font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; }
    </style>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-50 text-slate-800 antialiased">
    <main class="mx-auto flex min-h-screen max-w-md items-center px-4 py-8">
        <div class="w-full rounded-2xl bg-white p-6 text-center shadow-sm ring-1 ring-slate-200">
            <div class="mx-auto mb-5 flex h-14 w-14 items-center justify-center rounded-full bg-amber-100 text-amber-600">
                <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z" />
                </svg>
            </div>
            <h1 class="text-xl font-semibold leading-7 text-slate-900">
                {{ $message ?? __('Este enlace de encuesta no es válido o ya no está activo.') }}
            </h1>
            <button
                type="button"
                onclick="window.close()"
                class="mt-6 inline-flex w-full items-center justify-center rounded-xl bg-amber-500 px-4 py-3 text-base font-semibold text-white shadow-sm transition hover:bg-amber-600 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2"
            >
                {{ __('Cerrar') }}
            </button>
        </div>
    </main>
</body>
</html>

