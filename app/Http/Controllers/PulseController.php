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
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PulseController extends Controller
{
    /**
     * Login PWA "El Pulso del Día" (formulario).
     */
    public function login(): Response
    {
        $response = response()->view('pulse-login');
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

        $user = User::findByIdentifier($validated['email']);
        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Credenciales incorrectas.'],
            ]);
        }

        Auth::guard('web')->login($user, (bool) $request->boolean('remember'));
        $request->session()->regenerate();

        if (! $user->isClientOwner() || ! $user->ownedClient) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            throw ValidationException::withMessages([
                'email' => ['Solo los propietarios de cliente pueden acceder a El Pulso del Día.'],
            ]);
        }

        $code = $user->ownedClient->code;

        if ($request->wantsJson()) {
            return response()->json(['redirect' => url("/pulse/{$code}")]);
        }

        return redirect()->intended('/pulse/' . $code)->setStatusCode(303);
    }

    /**
     * Dashboard PWA por cliente (solo owner de ese cliente).
     */
    public function dashboard(string $clientCode): View|Response
    {
        $user = Auth::user();
        if (! $user->isClientOwner() || ! $user->ownedClient || $user->ownedClient->code !== $clientCode) {
            abort(403, 'No tiene acceso a este cliente.');
        }

        $client = $user->ownedClient;

        return view('pulse', [
            'client' => $client,
            'client_code' => $client->code,
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
            'background_color' => '#f8fafc',
            'theme_color' => '#0f766e',
            'icons' => [
                ['src' => url('/favicon.ico'), 'sizes' => '192x192', 'type' => 'image/x-icon', 'purpose' => 'any'],
                ['src' => url('/favicon.ico'), 'sizes' => '512x512', 'type' => 'image/x-icon', 'purpose' => 'any'],
            ],
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
        $cacheName = 'pulse-pwa-' . preg_replace('/[^a-zA-Z0-9_-]/', '', $code) . '-v1';
        $pulseUrl = url("/pulse/{$code}");
        $manifestUrl = url("/pulse/{$code}/manifest.json");
        $apiUrl = url("/api/pulse/{$code}");

        $js = <<<SW
const CACHE_NAME = "{$cacheName}";
const PULSE_URL = "{$pulseUrl}";
const MANIFEST_URL = "{$manifestUrl}";

self.addEventListener('install', (e) => {
  e.waitUntil(
    caches.open(CACHE_NAME).then((c) => c.addAll([PULSE_URL, MANIFEST_URL])).then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (e) => {
  e.waitUntil(
    caches.keys().then((keys) =>
      Promise.all(keys.filter((k) => k !== CACHE_NAME).map((k) => caches.delete(k)))
    ).then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (e) => {
  if (e.request.method !== 'GET') return;
  if (new URL(e.request.url).pathname.indexOf('/api/') !== -1) return;
  e.respondWith(
    caches.match(e.request).then((cached) =>
      cached || fetch(e.request).then((res) => {
        const u = new URL(e.request.url);
        if (u.origin === location.origin && (u.pathname.startsWith('/pulse/') || u.pathname.indexOf('/manifest') !== -1))
          caches.open(CACHE_NAME).then((c) => c.put(e.request, res.clone()));
        return res;
      })
    )
  );
});
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

    private function authorizeClient(string $clientCode): void
    {
        $user = Auth::user();
        if (! $user || ! $user->isClientOwner() || ! $user->ownedClient || $user->ownedClient->code !== $clientCode) {
            abort(403);
        }
    }
}
