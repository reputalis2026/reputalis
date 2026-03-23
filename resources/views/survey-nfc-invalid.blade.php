<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Encuesta no válida') }}</title>
    <style>
        body { font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; }
    </style>
</head>
<body class="min-h-screen bg-slate-50 text-slate-800 antialiased">
    <main class="mx-auto max-w-md px-4 py-12">
        <div class="rounded-xl bg-white shadow-sm ring-1 ring-slate-200 p-6">
            <h1 class="text-xl font-semibold mb-3">{{ __('Este enlace de encuesta no es válido o ya no está activo') }}</h1>
            <p class="text-slate-600 mb-6">
                {{ __('Pide a tu farmacia/centro un enlace NFC actualizado.') }}
            </p>
            <a href="{{ url('/survey') }}" class="inline-flex items-center justify-center rounded-lg bg-amber-500 px-4 py-2 text-white font-medium hover:bg-amber-600">
                {{ __('Ir a la página de encuestas') }}
            </a>
        </div>
    </main>
</body>
</html>

