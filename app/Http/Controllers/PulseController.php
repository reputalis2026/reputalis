<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Support\CsatMetrics;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
class PulseController extends Controller
{
    /**
     * Login PWA "El Pulso del Día" (formulario).
     */
    public function login(Request $request): Response|\Illuminate\View\View
    {
        $user = Auth::user();
        if ($user && $user->isClientOwner() && $user->ownedClient && $user->ownedClient->is_active) {
            return app(PulseScreenController::class)->home($request, $user->ownedClient->code, true);
        }

        $response = response()->view('pulse-login', [
            'title' => 'El Pulso del Día — Iniciar sesión',
            'pulseBoot' => 'login',
        ]);
        $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        return $response;
    }

    /**
     * Autenticar y redirigir a /pulse/{client_code} del owner.
     */
    public function authenticate(Request $request): JsonResponse|RedirectResponse|Response
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'El usuario o correo es obligatorio.',
            'password.required' => 'La contraseña es obligatoria.',
        ]);

        $emailKey = strtolower(trim((string) $validated['email']));
        $rateKey = 'pulse_login:' . $request->ip() . ':' . $emailKey;
        $maxAttempts = 5;
        $decaySeconds = 60;

        if (RateLimiter::tooManyAttempts($rateKey, $maxAttempts)) {
            throw ValidationException::withMessages([
                'email' => ['Credenciales incorrectas.'],
            ]);
        }

        $user = User::findByIdentifier($validated['email']);
        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            RateLimiter::hit($rateKey, $decaySeconds);
            throw ValidationException::withMessages([
                'email' => ['Credenciales incorrectas.'],
            ]);
        }

        if (! $user->isClientOwner() || ! $user->ownedClient) {
            RateLimiter::hit($rateKey, $decaySeconds);
            throw ValidationException::withMessages([
                'email' => ['Solo los propietarios de cliente pueden acceder a El Pulso del Día.'],
            ]);
        }

        $client = $user->ownedClient;
        if (! $client->is_active) {
            RateLimiter::hit($rateKey, $decaySeconds);
            throw ValidationException::withMessages([
                'email' => ['Tu usuario está inactivo.'],
            ]);
        }

        RateLimiter::clear($rateKey);
        Auth::guard('web')->login($user, (bool) $request->boolean('remember'));
        $request->session()->regenerate();
        $request->session()->forget('pulse_splash_seen');

        if ($request->wantsJson()) {
            return response()->json(['redirect' => url('/pulse?entrada=1')]);
        }

        return redirect('/pulse?entrada=1')->setStatusCode(303);
    }

    /**
     * Manifest público: una sola app, el icono abre el login.
     */
    public function appManifest(): JsonResponse
    {
        $manifest = [
            'id' => '/pulse',
            'name' => 'El Pulso del Día',
            'short_name' => 'Pulso',
            'description' => 'Dashboard diario de tu farmacia',
            'start_url' => '/pulse',
            'scope' => '/',
            'display' => 'standalone',
            'orientation' => 'portrait',
            'background_color' => '#e7f3ef',
            'theme_color' => '#e7f3ef',
            'icons' => $this->manifestIcons(),
        ];

        return response()->json($manifest)
            ->header('Content-Type', 'application/manifest+json')
            ->header('Cache-Control', 'no-cache');
    }

    /**
     * Service worker de la app pública. No cachea el login: el formulario lleva CSRF.
     */
    public function appServiceWorker(): Response
    {
        $js = <<<'SW'
self.addEventListener('install', (event) => {
  event.waitUntil(self.skipWaiting());
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys()
      .then((keys) => Promise.all(keys.map((key) => caches.delete(key))))
      .then(() => self.clients.claim())
  );
});

// pulse-sw 3. Sin fetch/respondWith: la navegación la pinta el navegador y no hay destello negro.
SW;

        return response($js, 200, [
            'Content-Type' => 'application/javascript; charset=UTF-8',
            'Cache-Control' => 'no-cache',
            'Service-Worker-Allowed' => '/',
        ]);
    }

    /**
     * Manifest PWA "El Pulso del Día" por cliente.
     */
    public function manifest(string $clientCode): Response
    {
        $this->authorizeClient($clientCode);
        $client = Client::where('code', $clientCode)->where('is_active', true)->firstOrFail();

        $manifest = [
            'name' => 'El Pulso del Día - ' . $client->namecommercial,
            'short_name' => 'Pulso ' . $client->code,
            'description' => 'Dashboard diario - ' . $client->namecommercial,
            'start_url' => url("/pulse/{$client->code}"),
            'scope' => url("/pulse/{$client->code}"),
            'display' => 'standalone',
            'orientation' => 'portrait',
            'background_color' => '#e7f3ef',
            'theme_color' => '#e7f3ef',
            'icons' => $this->manifestIcons(),
        ];

        return response()->json($manifest)
            ->header('Content-Type', 'application/manifest+json')
            ->header('Cache-Control', 'private, max-age=3600');
    }

    /**
     * Service Worker PWA Pulse por cliente.
     */
    public function sw(string $clientCode): Response
    {
        $this->authorizeClient($clientCode);
        $client = Client::where('code', $clientCode)->where('is_active', true)->firstOrFail();

        $code = $client->code;
        $cacheName = 'pulse-pwa-' . preg_replace('/[^a-zA-Z0-9_-]/', '', $code) . '-v2';

        $js = <<<SW
const CACHE_NAME = "{$cacheName}";

self.addEventListener('install', (e) => {
  e.waitUntil(self.skipWaiting());
});

self.addEventListener('activate', (e) => {
  e.waitUntil(
    caches.keys().then((keys) =>
      Promise.all(keys.filter((k) => k !== CACHE_NAME).map((k) => caches.delete(k)))
    ).then(() => self.clients.claim())
  );
});

// pulse-sw 3. Sin fetch/respondWith: la navegación la pinta el navegador y no hay destello negro.
SW;

        return response($js, 200, [
            'Content-Type' => 'application/javascript',
            'Cache-Control' => 'private, max-age=3600',
            'Service-Worker-Allowed' => '/',
        ]);
    }

    /**
     * API: métricas día + acumulado para la PWA (solo owner).
     */
    public function metrics(string $clientCode): JsonResponse
    {
        $this->authorizeClient($clientCode);
        $client = Client::where('code', $clientCode)->where('is_active', true)->firstOrFail();

        $today = CsatMetrics::getMetrics($client->id, CsatMetrics::PERIOD_TODAY);
        $accumulated = CsatMetrics::getMetrics($client->id, CsatMetrics::PERIOD_ALL);

        return response()->json([
            'today' => [
                'avg_score' => $today['avg_score'],
                'count' => $today['today_count'],
            ],
            'accumulated' => [
                'avg_score' => $accumulated['avg_score'],
                'total' => $accumulated['total'],
                'satisfied_pct' => $accumulated['satisfied_pct'],
            ],
        ]);
    }

    /**
     * El icono de la gráfica queda para la pantalla de inicio (maskable).
     * El arranque de Android usa el icono "any": un cuadrado del mismo verde
     * que el fondo, para que no se vea el icono en grande.
     *
     * @return array<int, array<string, string>>
     */
    private function manifestIcons(): array
    {
        return [
            [
                'src' => url('/img/pulse-splash-flat-192.png'),
                'sizes' => '192x192',
                'type' => 'image/png',
                'purpose' => 'any',
            ],
            [
                'src' => url('/img/pulse-splash-flat-512.png'),
                'sizes' => '512x512',
                'type' => 'image/png',
                'purpose' => 'any',
            ],
            [
                'src' => url('/img/pulse-icon-192.png'),
                'sizes' => '192x192',
                'type' => 'image/png',
                'purpose' => 'maskable',
            ],
            [
                'src' => url('/img/pulse-icon-512.png'),
                'sizes' => '512x512',
                'type' => 'image/png',
                'purpose' => 'maskable',
            ],
        ];
    }

    private function authorizeClient(string $clientCode): void
    {
        $user = Auth::user();
        if (! $user || ! $user->isClientOwner() || ! $user->ownedClient || $user->ownedClient->code !== $clientCode) {
            abort(403);
        }
    }
}
