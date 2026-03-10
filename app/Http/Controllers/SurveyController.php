<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ImprovementReason;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class SurveyController extends Controller
{
    /**
     * Landing /survey: selector de cliente.
     * PWA /survey/{client_code}: encuesta fija a ese cliente.
     */
    public function show(?string $clientCode = null): View|Response
    {
        if ($clientCode === null) {
            $clients = Client::query()
                ->where('is_active', true)
                ->orderBy('namecommercial')
                ->get(['id', 'code', 'namecommercial']);

            return view('survey', [
                'client' => null,
                'clients' => $clients,
                'improvementBlock' => null,
            ]);
        }

        $client = Client::query()
            ->where('code', $clientCode)
            ->where('is_active', true)
            ->firstOrFail();

        $config = $client->improvementConfig;
        $improvementBlock = null;
        if ($config) {
            $options = $config->options()->orderBy('sort_order')->orderBy('created_at')->get();
            if ($options->count() >= 2) {
                $improvementBlock = [
                    'title' => $config->title,
                    'options' => $options->map(fn ($o) => ['id' => $o->id, 'label' => $o->label])->values()->all(),
                ];
            }
        }

        return view('survey', [
            'client' => $client,
            'clients' => collect(),
            'improvementBlock' => $improvementBlock,
            'client_code' => $client->code,
        ]);
    }

    /**
     * Manifest dinámico por cliente (PWA).
     */
    public function manifest(string $clientCode): Response
    {
        $client = Client::query()
            ->where('code', $clientCode)
            ->where('is_active', true)
            ->firstOrFail();

        $baseUrl = url('/');
        $manifest = [
            'name' => 'Reputalis - ' . $client->namecommercial,
            'short_name' => $client->code,
            'description' => 'Encuesta de satisfacción - ' . $client->namecommercial,
            'start_url' => url("/survey/{$client->code}"),
            'scope' => url("/survey/{$client->code}"),
            'display' => 'standalone',
            'orientation' => 'portrait',
            'background_color' => '#f8fafc',
            'theme_color' => '#f59e0b',
            'icons' => [
                ['src' => $baseUrl . '/favicon.ico', 'sizes' => '48x48', 'type' => 'image/x-icon', 'purpose' => 'any'],
                ['src' => $baseUrl . '/favicon.ico', 'sizes' => '192x192', 'type' => 'image/x-icon', 'purpose' => 'any'],
                ['src' => $baseUrl . '/favicon.ico', 'sizes' => '512x512', 'type' => 'image/x-icon', 'purpose' => 'any'],
            ],
        ];

        return response()->json($manifest)
            ->header('Content-Type', 'application/manifest+json')
            ->header('Cache-Control', 'public, max-age=3600');
    }

    /**
     * Service Worker dinámico por cliente.
     */
    public function sw(string $clientCode): Response
    {
        $client = Client::query()
            ->where('code', $clientCode)
            ->where('is_active', true)
            ->firstOrFail();

        $code = $client->code;
        $cacheName = 'reputalis-pwa-' . preg_replace('/[^a-zA-Z0-9_-]/', '', $code) . '-v1';
        $scope = url("/survey/{$code}");
        $manifestUrl = url("/manifest/{$code}.json");
        $surveyUrl = url("/survey/{$code}");
        $apiUrl = url('/api/surveys/create');

        $js = <<<SW
const CACHE_NAME = "{$cacheName}";
const SURVEY_URL = "{$surveyUrl}";
const MANIFEST_URL = "{$manifestUrl}";
const API_URL = "{$apiUrl}";

self.addEventListener('install', (e) => {
  e.waitUntil(
    caches.open(CACHE_NAME).then((cache) =>
      cache.addAll([SURVEY_URL, MANIFEST_URL])
    ).then(() => self.skipWaiting())
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
  const url = new URL(e.request.url);
  if (e.request.method !== 'GET') return;
  if (url.pathname === '/api/surveys/create') return;
  e.respondWith(
    caches.match(e.request).then((cached) =>
      cached || fetch(e.request).then((res) => {
        const clone = res.clone();
        if (url.origin === location.origin && (url.pathname.startsWith('/survey/') || url.pathname.startsWith('/manifest/')))
          caches.open(CACHE_NAME).then((cache) => cache.put(e.request, clone));
        return res;
      })
    )
  );
});
SW;

        return response($js, 200, [
            'Content-Type' => 'application/javascript',
            'Cache-Control' => 'public, max-age=3600',
            'Service-Worker-Allowed' => '/',
        ]);
    }
}
