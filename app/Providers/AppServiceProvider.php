<?php

namespace App\Providers;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->overrideFilamentPanelViews();
        $this->forceLivewirePublishedAssetUrl();
        $this->configureSurveyRateLimiting();
        $this->configurePulseUnauthenticatedRedirect();
    }

    /**
     * Sobrescribir vistas del panel Filament (p. ej. logo) desde resources/views/vendor/filament-panels/.
     */
    protected function overrideFilamentPanelViews(): void
    {
        $path = resource_path('views/vendor/filament-panels');
        if (is_dir($path)) {
            View::prependNamespace('filament-panels', $path);
        }
    }

    /**
     * Redirigir a /pulse (login PWA) cuando falla la auth en rutas /pulse/*.
     */
    protected function configurePulseUnauthenticatedRedirect(): void
    {
        AuthenticationException::redirectUsing(function (Request $request) {
            if (str_starts_with($request->path(), 'pulse')) {
                return url('/pulse');
            }

            return url('/admin');
        });
    }

    /**
     * Rate limiting para el endpoint de encuestas: 5 por IP por minuto.
     */
    protected function configureSurveyRateLimiting(): void
    {
        RateLimiter::for('surveys', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });
    }

    /**
     * Fuerza que Livewire use los assets publicados (/vendor/livewire/...) en lugar de
     * la ruta dinámica /livewire/livewire.js, que puede devolver 404 si el servidor
     * (p. ej. nginx) no reenvía esa petición a Laravel.
     */
    protected function forceLivewirePublishedAssetUrl(): void
    {
        $manifestPath = public_path('vendor/livewire/manifest.json');
        if (! file_exists($manifestPath)) {
            return;
        }
        $manifest = json_decode((string) file_get_contents($manifestPath), true);
        $version = $manifest['/livewire.js'] ?? '';
        $fileName = config('app.debug') ? 'livewire.js' : 'livewire.min.js';
        config([
            'livewire.asset_url' => url("vendor/livewire/{$fileName}?id={$version}"),
        ]);
    }
}
