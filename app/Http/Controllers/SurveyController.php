<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ClientImprovementConfig;
use App\Models\ClientImprovementOption;
use App\Models\ImprovementReason;
use App\Models\NfcToken;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class SurveyController extends Controller
{
    /**
     * Landing /survey: selector de cliente.
     * PWA /survey/{client_code}: encuesta fija a ese cliente.
     */
    public function show(Request $request, ?string $clientCode = null): View|Response
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
                'surveyDisplayMode' => ClientImprovementConfig::DISPLAY_MODE_NUMBERS,
                'surveyLocale' => ClientImprovementConfig::DEFAULT_LOCALE,
                'surveyPositiveScores' => ClientImprovementConfig::defaultPositiveScores(),
            ]);
        }

        $client = Client::query()
            ->where('code', $clientCode)
            ->where('is_active', true)
            ->firstOrFail();

        $client->load('improvementConfig');
        $config = $client->improvementConfig;
        $options = $config ? $config->options()->orderBy('sort_order')->orderBy('created_at')->get() : collect();
        $surveyLocale = $this->resolveSurveyLocale($request, $config, $options);
        $surveyQuestion = $config?->surveyQuestionTextForLocale($surveyLocale)
            ?? ClientImprovementConfig::defaultSurveyQuestionTexts()[ClientImprovementConfig::DEFAULT_LOCALE];
        $surveyDisplayMode = ClientImprovementConfig::normalizeDisplayMode($config?->display_mode);
        $surveyPositiveScores = $config?->positiveScores() ?? ClientImprovementConfig::defaultPositiveScores();
        $improvementBlock = null;
        if ($config) {
            if ($options->count() >= 2) {
                $improvementBlock = [
                    'title' => $config->titleForLocale($surveyLocale),
                    'options' => $options->map(fn (ClientImprovementOption $o) => [
                        'id' => $o->id,
                        'label' => $o->labelForLocale($surveyLocale),
                    ])->values()->all(),
                ];
            }
        }

        return response()->view('survey', [
            'client' => $client,
            'clients' => collect(),
            'improvementBlock' => $improvementBlock,
            'client_code' => $client->code,
            'surveyDisplayMode' => $surveyDisplayMode,
            'surveyQuestion' => $surveyQuestion,
            'surveyLocale' => $surveyLocale,
            'surveyPositiveScores' => $surveyPositiveScores,
        ])->header('Vary', 'Accept-Language');
    }

    /**
     * Encuesta pública vía NFC:
     * GET /survey/nfc/{token}
     *
     * Resuelve token activo -> cliente + empleado y renderiza la misma vista
     * de encuesta CSAT, pero preasignando internamente el empleado.
     */
    public function showNfc(Request $request, string $token): View|Response
    {
        $nfcToken = NfcToken::query()
            ->where('token', $token)
            ->where('is_active', true)
            ->with(['client', 'employee'])
            ->first();

        if (! $nfcToken?->client || ! $nfcToken?->employee) {
            return response()->view('survey-nfc-invalid', [
                'message' => 'Este enlace de encuesta no es válido o ya no está activo.',
            ], 404);
        }

        /** @var Client $client */
        $client = $nfcToken->client;
        /** @var Employee $employee */
        $employee = $nfcToken->employee;

        // Validación extra: el empleado debe pertenecer al mismo cliente.
        if ((string) $employee->client_id !== (string) $client->id) {
            return response()->view('survey-nfc-invalid', [
                'message' => 'Este enlace de encuesta no es válido o ya no está activo.',
            ], 404);
        }

        $client->load('improvementConfig');
        $config = $client->improvementConfig;
        $options = $config ? $config->options()->orderBy('sort_order')->orderBy('created_at')->get() : collect();
        $surveyLocale = $this->resolveSurveyLocale($request, $config, $options);
        $surveyQuestion = $config?->surveyQuestionTextForLocale($surveyLocale)
            ?? ClientImprovementConfig::defaultSurveyQuestionTexts()[ClientImprovementConfig::DEFAULT_LOCALE];
        $surveyDisplayMode = ClientImprovementConfig::normalizeDisplayMode($config?->display_mode);
        $surveyPositiveScores = $config?->positiveScores() ?? ClientImprovementConfig::defaultPositiveScores();
        $improvementBlock = null;
        if ($config) {
            if ($options->count() >= 2) {
                $improvementBlock = [
                    'title' => $config->titleForLocale($surveyLocale),
                    'options' => $options->map(fn (ClientImprovementOption $o) => [
                        'id' => $o->id,
                        'label' => $o->labelForLocale($surveyLocale),
                    ])->values()->all(),
                ];
            }
        }

        return response()->view('survey', [
            'client' => $client,
            'clients' => collect(),
            'improvementBlock' => $improvementBlock,
            'client_code' => $client->code,
            'employee' => $employee,
            'employeeCode' => $employee->name, // La API resuelve empleado por name
            'showNfcDemo' => false,
            'surveyDisplayMode' => $surveyDisplayMode,
            'surveyQuestion' => $surveyQuestion,
            'surveyLocale' => $surveyLocale,
            'surveyPositiveScores' => $surveyPositiveScores,
        ])->header('Vary', 'Accept-Language');
    }

    private function resolveSurveyLocale(Request $request, ?ClientImprovementConfig $config, \Illuminate\Support\Collection $options): string
    {
        $detectedLocale = $this->detectRequestLocale($request);
        if ($detectedLocale && $this->hasCompleteTranslation($config, $options, $detectedLocale)) {
            return $detectedLocale;
        }

        $defaultLocale = ClientImprovementConfig::normalizeDefaultLocale($config?->default_locale);
        if ($this->hasCompleteTranslation($config, $options, $defaultLocale)) {
            return $defaultLocale;
        }

        return ClientImprovementConfig::DEFAULT_LOCALE;
    }

    private function detectRequestLocale(Request $request): ?string
    {
        foreach ($request->getLanguages() as $language) {
            $locale = ClientImprovementConfig::normalizeLocale($language);
            if ($locale) {
                return $locale;
            }
        }

        return ClientImprovementConfig::normalizeLocale($request->header('Accept-Language'));
    }

    private function hasCompleteTranslation(?ClientImprovementConfig $config, \Illuminate\Support\Collection $options, string $locale): bool
    {
        if (! $config?->hasTextForLocale($locale)) {
            return false;
        }

        return $options->count() >= 2
            && $options->every(fn (ClientImprovementOption $option): bool => $option->hasLabelForLocale($locale));
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
        $cacheName = 'reputalis-pwa-' . preg_replace('/[^a-zA-Z0-9_-]/', '', $code) . '-v5';
        $manifestUrl = url("/manifest/{$code}.json");
        $surveyUrl = url("/survey/{$code}");
        $apiUrl = url('/api/surveys/create');

        $precacheUrls = [$surveyUrl, $manifestUrl];
        for ($i = 1; $i <= 5; $i++) {
            $face = public_path("survey-rating/faces/cara{$i}.png");
            if (is_file($face)) {
                $precacheUrls[] = url("/survey-rating/faces/cara{$i}.png");
            }
            $num = public_path("survey-rating/numbers/{$i}.png");
            if (is_file($num)) {
                $precacheUrls[] = url("/survey-rating/numbers/{$i}.png");
            }
        }
        $precacheJson = json_encode(array_values(array_unique($precacheUrls)));

        $js = <<<SW
const CACHE_NAME = "{$cacheName}";
const SURVEY_URL = "{$surveyUrl}";
const MANIFEST_URL = "{$manifestUrl}";
const API_URL = "{$apiUrl}";
const PRECACHE_URLS = {$precacheJson};

self.addEventListener('install', (e) => {
  e.waitUntil(
    caches.open(CACHE_NAME).then((cache) =>
      Promise.all(
        PRECACHE_URLS.map((u) =>
          cache.add(u).catch((err) => {
            console.warn('[Reputalis SW] precache omitido:', u, err && err.message);
            return null;
          })
        )
      )
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
  const originOk = url.origin === self.location.origin;
  const cacheablePath =
    originOk &&
    (url.pathname.startsWith('/survey/') ||
      url.pathname.startsWith('/manifest/') ||
      url.pathname.startsWith('/survey-rating/'));
  e.respondWith(
    caches.match(e.request).then((cached) =>
        cached ||
        fetch(e.request).then((res) => {
          if (cacheablePath && res.ok) {
            const clone = res.clone();
            caches.open(CACHE_NAME).then((cache) => cache.put(e.request, clone));
          }
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
