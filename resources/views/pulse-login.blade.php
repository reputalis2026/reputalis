<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0f766e">
    <title>El Pulso del Día — Iniciar sesión</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-100 text-slate-800 antialiased flex flex-col items-center justify-center p-4">
    <div class="w-full max-w-sm">
        <div class="text-center mb-8">
            <h1 class="text-2xl font-semibold text-slate-800">El Pulso del Día</h1>
            <p class="text-slate-500 text-sm mt-1">Dashboard diario de tu cliente</p>
        </div>
        <form method="POST" action="{{ url('/pulse/login') }}" class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200 space-y-4">
            @csrf
            @if($errors->any())
                <div class="rounded-lg bg-red-50 text-red-700 text-sm px-3 py-2">
                    {{ $errors->first() }}
                </div>
            @endif
            <div>
                <label for="email" class="block text-sm font-medium text-slate-600 mb-1">Usuario o email</label>
                <input type="text" name="email" id="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                    class="w-full rounded-xl border border-slate-300 px-4 py-3 text-slate-800 focus:border-teal-500 focus:ring-1 focus:ring-teal-500"
                    placeholder="Usuario o email">
            </div>
            <div>
                <label for="password" class="block text-sm font-medium text-slate-600 mb-1">Contraseña</label>
                <input type="password" name="password" id="password" required
                    class="w-full rounded-xl border border-slate-300 px-4 py-3 text-slate-800 focus:border-teal-500 focus:ring-1 focus:ring-teal-500"
                    placeholder="••••••••">
            </div>
            <label class="flex items-center gap-2 text-sm text-slate-600">
                <input type="checkbox" name="remember" value="1" class="rounded border-slate-300 text-teal-600 focus:ring-teal-500">
                Recordarme
            </label>
            <button type="submit" class="w-full rounded-xl bg-teal-600 px-4 py-3 font-medium text-white hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2">
                Entrar
            </button>
        </form>
        <p class="text-center text-slate-400 text-xs mt-4">Solo propietarios de cliente</p>
    </div>
</body>
</html>
