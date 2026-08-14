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
     * Query para Outscraper: place_id si existe; si no, google_id.
     * (place_id es la fuente de alta; google_id se guarda tras el primer sync.)
     */
    public function resolveQuery(Client $client): ?string
    {
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
     * @return array{ok: bool, snapshot: ?ClientExternalReputationSnapshot, alert: ?ClientExternalReputationAlert, error: ?string}
     */
    public function syncClient(Client $client): array
    {
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

            if (! $metrics->hasStarsBreakdown()) {
                $error = 'La respuesta no incluye reviews_per_score usable.';
                $this->markError($client, $error);

                return ['ok' => false, 'snapshot' => null, 'alert' => null, 'error' => $error];
            }

            return $this->persistSnapshot($client, $metrics, ClientExternalReputationSnapshot::SOURCE_OUTSCRAPER);
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
    private function persistSnapshot(Client $client, PlaceMetrics $metrics, string $source): array
    {
        return DB::transaction(function () use ($client, $metrics, $source) {
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

            $client->forceFill([
                'google_id' => $metrics->googleId ?: $client->google_id,
                'google_place_id' => $client->google_place_id
                    ?: (Client::normalizeGooglePlaceId($metrics->placeId) ?? null),
                'external_reputation_last_synced_at' => $now,
                'external_reputation_last_error' => null,
            ])->save();

            $alert = $this->maybeCreateAlert($client, $previous, $snapshot);

            return [
                'ok' => true,
                'snapshot' => $snapshot,
                'alert' => $alert,
                'error' => null,
            ];
        });
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
