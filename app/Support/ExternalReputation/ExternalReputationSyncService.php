<?php

namespace App\Support\ExternalReputation;

use App\Models\Client;
use App\Models\ClientExternalReputationAlert;
use App\Models\ClientExternalReputationSnapshot;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ExternalReputationSyncService
{
    public function __construct(
        private readonly PlacesReputationGateway $gateway,
    ) {}

    /**
     * Query preferida para Outscraper Places.
     * Tras el bootstrap se usa la search_query de texto (1 ficha + desglose).
     * Si aún no existe: place_id / google_id (bootstrap).
     */
    public function resolveQuery(Client $client): ?string
    {
        $searchQuery = trim((string) ($client->external_reputation_search_query ?? ''));
        if ($searchQuery !== '') {
            return $searchQuery;
        }

        $placeId = Client::normalizeGooglePlaceId($client->google_place_id);
        if ($placeId !== null) {
            return $placeId;
        }

        $googleId = trim((string) ($client->google_id ?? ''));

        return $googleId !== '' ? $googleId : null;
    }

    /**
     * Sincroniza un cliente: snapshot + posible alerta 1★/2★.
     *
     * Coste Outscraper:
     * - Bootstrap (sin search_query): hasta 2 fichas (place_id + nombre/ciudad si hace falta).
     * - Syncs posteriores (con search_query): 1 ficha.
     *
     * @return array{ok: bool, snapshot: ?ClientExternalReputationSnapshot, alert: ?ClientExternalReputationAlert, error: ?string}
     */
    public function syncClient(Client $client): array
    {
        $isBootstrap = trim((string) ($client->external_reputation_search_query ?? '')) === '';
        $query = $this->resolveQuery($client);
        if ($query === null) {
            $error = 'Cliente sin google_place_id ni google_id.';
            $this->markError($client, $error);

            return ['ok' => false, 'snapshot' => null, 'alert' => null, 'error' => $error];
        }

        try {
            $results = $this->gateway->fetchPlaces([$query], 1);
            $metrics = $results[$query] ?? null;

            if (! $metrics instanceof PlaceMetrics) {
                $error = 'Outscraper no devolvió ficha para la query.';
                $this->markError($client, $error);

                return ['ok' => false, 'snapshot' => null, 'alert' => null, 'error' => $error];
            }

            $searchQueryToPersist = null;

            if ($isBootstrap) {
                // place_id / google_id a menudo no traen reviews_per_score; 2ª ficha solo en bootstrap.
                if (! $metrics->hasStarsBreakdown()) {
                    $fallbackQuery = $this->buildNameLocationQuery($metrics)
                        ?? $this->buildNameLocationQueryFromClient($client);

                    if ($fallbackQuery !== null && $fallbackQuery !== $query) {
                        $fallbackResults = $this->gateway->fetchPlaces([$fallbackQuery], 1);
                        $fallbackMetrics = $fallbackResults[$fallbackQuery] ?? null;

                        if ($fallbackMetrics instanceof PlaceMetrics && $fallbackMetrics->hasStarsBreakdown()) {
                            if (! $this->placeIdMatchesClient($client, $fallbackMetrics)) {
                                $error = 'La ficha de Outscraper no coincide con el Place ID del cliente.';
                                $this->markError($client, $error);

                                return ['ok' => false, 'snapshot' => null, 'alert' => null, 'error' => $error];
                            }

                            $metrics = new PlaceMetrics(
                                query: $fallbackQuery,
                                placeId: $fallbackMetrics->placeId ?: $metrics->placeId,
                                googleId: $fallbackMetrics->googleId ?: $metrics->googleId,
                                name: $fallbackMetrics->name ?: $metrics->name,
                                rating: $fallbackMetrics->rating ?? $metrics->rating,
                                reviewsTotal: $fallbackMetrics->reviewsTotal,
                                stars: $fallbackMetrics->stars,
                                raw: $fallbackMetrics->raw ?? $metrics->raw,
                            );
                            $searchQueryToPersist = $fallbackQuery;
                        }
                    }
                } else {
                    // Raro: la 1ª query ya trajo desglose; guardar query de texto para siguientes syncs.
                    $searchQueryToPersist = $this->buildNameLocationQuery($metrics)
                        ?? $this->buildNameLocationQueryFromClient($client);
                }
            } else {
                // Sync barato: validar que sigue siendo el mismo negocio.
                if (! $this->placeIdMatchesClient($client, $metrics)) {
                    $error = 'La ficha de Outscraper no coincide con el Place ID del cliente.';
                    $this->markError($client, $error);

                    return ['ok' => false, 'snapshot' => null, 'alert' => null, 'error' => $error];
                }
            }

            if (! $metrics->hasStarsBreakdown()) {
                $error = 'La respuesta no incluye reviews_per_score usable.';
                $this->markError($client, $error);

                return ['ok' => false, 'snapshot' => null, 'alert' => null, 'error' => $error];
            }

            return $this->persistSnapshot(
                $client,
                $metrics,
                ClientExternalReputationSnapshot::SOURCE_OUTSCRAPER,
                $searchQueryToPersist,
            );
        } catch (Throwable $e) {
            Log::warning('external_reputation.sync_failed', [
                'client_id' => $client->id,
                'message' => $e->getMessage(),
            ]);
            $this->markError($client, $e->getMessage());

            return [
                'ok' => false,
                'snapshot' => null,
                'alert' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Outscraper suele devolver reviews_per_score en búsquedas por texto, no por place_id.
     */
    private function buildNameLocationQuery(PlaceMetrics $metrics): ?string
    {
        $name = trim((string) ($metrics->name ?? ''));
        if ($name === '') {
            return null;
        }

        $city = trim((string) (($metrics->raw['city'] ?? null) ?: ''));
        $parts = array_values(array_filter([$name, $city !== '' ? $city : null]));

        return $parts === [] ? null : implode(', ', $parts);
    }

    private function buildNameLocationQueryFromClient(Client $client): ?string
    {
        $name = trim((string) ($client->namecommercial ?? ''));
        if ($name === '') {
            return null;
        }

        $city = trim((string) ($client->ciudad ?? ''));
        $parts = array_values(array_filter([$name, $city !== '' ? $city : null]));

        return $parts === [] ? null : implode(', ', $parts);
    }

    private function placeIdMatchesClient(Client $client, PlaceMetrics $metrics): bool
    {
        $expected = Client::normalizeGooglePlaceId($client->google_place_id);
        if ($expected === null) {
            return true;
        }

        $actual = Client::normalizeGooglePlaceId($metrics->placeId);

        return $actual === null || $actual === $expected;
    }

    /**
     * Simula un sync de cron con datos fake: parte del último snapshot (o base)
     * y suma +1 a 1★ y +1 a 5★ para que cada pulsación sea distinta y dispare alerta.
     *
     * @return array{ok: bool, snapshot: ?ClientExternalReputationSnapshot, alert: ?ClientExternalReputationAlert, error: ?string}
     */
    public function simulateIncrementalSync(Client $client): array
    {
        try {
            $previous = $client->externalReputationSnapshots()
                ->orderByDesc('captured_at')
                ->first();

            $metrics = $this->buildIncrementalFakeMetrics($client, $previous);

            return $this->persistSnapshot(
                $client,
                $metrics,
                ClientExternalReputationSnapshot::SOURCE_SIMULATED,
            );
        } catch (Throwable $e) {
            Log::warning('external_reputation.simulate_failed', [
                'client_id' => $client->id,
                'message' => $e->getMessage(),
            ]);
            $this->markError($client, $e->getMessage());

            return [
                'ok' => false,
                'snapshot' => null,
                'alert' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Sincroniza varios clientes; un fallo no detiene el resto.
     *
     * @param  iterable<int, Client>  $clients
     * @return array{ok: int, failed: int, results: list<array{client_id: string, ok: bool, error: ?string}>}
     */
    public function syncClients(iterable $clients): array
    {
        $ok = 0;
        $failed = 0;
        $results = [];

        foreach ($clients as $client) {
            $result = $this->syncClient($client);
            if ($result['ok']) {
                $ok++;
            } else {
                $failed++;
            }
            $results[] = [
                'client_id' => (string) $client->id,
                'ok' => $result['ok'],
                'error' => $result['error'],
            ];
        }

        return compact('ok', 'failed', 'results');
    }

    /**
     * @return array{ok: bool, snapshot: ?ClientExternalReputationSnapshot, alert: ?ClientExternalReputationAlert, error: ?string}
     */
    private function persistSnapshot(
        Client $client,
        PlaceMetrics $metrics,
        string $source,
        ?string $searchQueryToPersist = null,
    ): array {
        return DB::transaction(function () use ($client, $metrics, $source, $searchQueryToPersist) {
            $previous = $client->externalReputationSnapshots()
                ->orderByDesc('captured_at')
                ->first();

            $now = Carbon::now();
            $snapshotDate = Carbon::now('Europe/Madrid')->toDateString();
            $calculated = RatingProjection::calculatedRating($metrics->stars);

            $snapshot = ClientExternalReputationSnapshot::query()->create([
                'client_id' => $client->id,
                'captured_at' => $now,
                'snapshot_date' => $snapshotDate,
                'rating' => $metrics->rating,
                'reviews_total' => $metrics->reviewsTotal,
                'stars_1' => $metrics->stars[1],
                'stars_2' => $metrics->stars[2],
                'stars_3' => $metrics->stars[3],
                'stars_4' => $metrics->stars[4],
                'stars_5' => $metrics->stars[5],
                'calculated_rating' => $calculated,
                'source' => $source,
                'raw_payload' => $metrics->raw,
            ]);

            $clientFill = [
                'google_id' => $metrics->googleId ?: $client->google_id,
                'google_place_id' => $client->google_place_id
                    ?: (Client::normalizeGooglePlaceId($metrics->placeId) ?? null),
                'external_reputation_last_synced_at' => $now,
                'external_reputation_last_error' => null,
            ];

            if ($searchQueryToPersist !== null && trim($searchQueryToPersist) !== '') {
                $clientFill['external_reputation_search_query'] = trim($searchQueryToPersist);
            }

            $client->forceFill($clientFill)->save();

            $alert = $this->maybeCreateAlert($client, $previous, $snapshot);

            return [
                'ok' => true,
                'snapshot' => $snapshot,
                'alert' => $alert,
                'error' => null,
            ];
        });
    }

    /**
     * @return array{ok: bool, snapshot: ?ClientExternalReputationSnapshot, alert: ?ClientExternalReputationAlert, error: ?string}
     */
    public function simulateSingleReview(Client $client, int $starRating): array
    {
        try {
            $previous = $client->externalReputationSnapshots()
                ->orderByDesc('captured_at')
                ->first();

            $metrics = $this->buildSingleReviewFakeMetrics($client, $previous, $starRating);

            return $this->persistSnapshot(
                $client,
                $metrics,
                ClientExternalReputationSnapshot::SOURCE_SIMULATED,
            );
        } catch (Throwable $e) {
            Log::warning('external_reputation.simulate_review_failed', [
                'client_id' => $client->id,
                'message' => $e->getMessage(),
            ]);
            $this->markError($client, $e->getMessage());

            return [
                'ok' => false,
                'snapshot' => null,
                'alert' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    private function buildSingleReviewFakeMetrics(
        Client $client,
        ?ClientExternalReputationSnapshot $previous,
        int $starRating,
    ): PlaceMetrics {
        $starRating = max(1, min(5, $starRating));

        if ($previous) {
            $stars = [
                1 => (int) $previous->stars_1,
                2 => (int) $previous->stars_2,
                3 => (int) $previous->stars_3,
                4 => (int) $previous->stars_4,
                5 => (int) $previous->stars_5,
            ];
        } else {
            $stars = [1 => 0, 2 => 0, 3 => 2, 4 => 8, 5 => 20];
        }

        $stars[$starRating]++;

        $total = array_sum($stars);
        $weighted = 1 * $stars[1] + 2 * $stars[2] + 3 * $stars[3] + 4 * $stars[4] + 5 * $stars[5];
        $rating = $total > 0 ? round($weighted / $total, 1) : null;

        $placeId = Client::normalizeGooglePlaceId($client->google_place_id)
            ?? ('ChIJSim'.substr(str_replace('-', '', (string) $client->id), 0, 20));
        $googleId = trim((string) ($client->google_id ?? '')) !== ''
            ? (string) $client->google_id
            : ('0xsim:'.substr(md5((string) $client->id), 0, 12));

        return new PlaceMetrics(
            query: $placeId,
            placeId: $placeId,
            googleId: $googleId,
            name: 'Simulated place '.$client->code,
            rating: $rating,
            reviewsTotal: $total,
            stars: $stars,
            raw: [
                'name' => 'Simulated place '.$client->code,
                'rating' => $rating,
                'reviews' => $total,
                'reviews_per_score' => $stars,
                'simulated_review' => $starRating.'★',
            ],
        );
    }

    private function buildIncrementalFakeMetrics(
        Client $client,
        ?ClientExternalReputationSnapshot $previous,
    ): PlaceMetrics {
        if ($previous) {
            $stars = [
                1 => (int) $previous->stars_1 + 1,
                2 => (int) $previous->stars_2,
                3 => (int) $previous->stars_3,
                4 => (int) $previous->stars_4,
                5 => (int) $previous->stars_5 + 1,
            ];
        } else {
            $stars = [
                1 => 1,
                2 => 0,
                3 => 2,
                4 => 8,
                5 => 20,
            ];
        }

        $total = array_sum($stars);
        $weighted = 1 * $stars[1] + 2 * $stars[2] + 3 * $stars[3] + 4 * $stars[4] + 5 * $stars[5];
        $rating = $total > 0 ? round($weighted / $total, 1) : null;

        $placeId = Client::normalizeGooglePlaceId($client->google_place_id)
            ?? ('ChIJSim'.substr(str_replace('-', '', (string) $client->id), 0, 20));
        $googleId = trim((string) ($client->google_id ?? '')) !== ''
            ? (string) $client->google_id
            : ('0xsim:'.substr(md5((string) $client->id), 0, 12));

        return new PlaceMetrics(
            query: $placeId,
            placeId: $placeId,
            googleId: $googleId,
            name: 'Simulated place '.$client->code,
            rating: $rating,
            reviewsTotal: $total,
            stars: $stars,
            raw: [
                'name' => 'Simulated place '.$client->code,
                'rating' => $rating,
                'reviews' => $total,
                'reviews_per_score' => [
                    '1' => $stars[1],
                    '2' => $stars[2],
                    '3' => $stars[3],
                    '4' => $stars[4],
                    '5' => $stars[5],
                ],
                'simulated' => true,
                'increment' => '+1 stars_1 / +1 stars_5',
            ],
        );
    }

    private function maybeCreateAlert(
        Client $client,
        ?ClientExternalReputationSnapshot $previous,
        ClientExternalReputationSnapshot $current,
    ): ?ClientExternalReputationAlert {
        $detected = app(NegativeReviewAlertDetector::class)->detect($previous, $current);
        if ($detected === null) {
            return null;
        }

        return ClientExternalReputationAlert::query()->create([
            'client_id' => $client->id,
            'detected_at' => $current->captured_at,
            'kind' => $detected['kind'],
            'delta_stars_1' => $detected['delta_stars_1'],
            'delta_stars_2' => $detected['delta_stars_2'],
            'delta_reviews_total' => $detected['delta_reviews_total'],
            'from_snapshot_id' => $previous?->id,
            'to_snapshot_id' => $current->id,
        ]);
    }

    private function markError(Client $client, string $error): void
    {
        $client->forceFill([
            'external_reputation_last_error' => mb_substr($error, 0, 2000),
        ])->save();
    }
}
