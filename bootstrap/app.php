<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Excluir el POST de login del panel y PWA para evitar 419 (token expirado/inválido en el formulario)
        $middleware->validateCsrfTokens(except: [
            'admin/login',
            'pulse/login',
        ]);
        $middleware->redirectGuestsTo(function (Request $request) {
            if ($request->is('pulse', 'pulse/*')) {
                return '/pulse';
            }

            return '/admin/login';
        });
        $middleware->alias([
            'pulse.nocache' => \App\Http\Middleware\PreventPulsePageCache::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
