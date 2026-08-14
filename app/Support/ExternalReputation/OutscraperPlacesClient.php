<?php

namespace App\Support\ExternalReputation;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Cliente HTTP Outscraper Places (`GET /maps/search` o `/google-maps-search`).
 *
 * @see https://app.outscraper.cloud/api-docs#tag/google/GET/maps/search
 */
class OutscraperPlacesClient implements PlacesReputationGateway
{
    public function __construct(
        private readonly ?string $apiKey = null,
        private readonly string $baseUrl = 'https://api.outscraper.com',
        private readonly int $timeout = 60,
        private readonly int $retries = 2,
        private readonly string $language = 'es',
        private readonly string $region = 'ES',
        private readonly string $endpoint = '/maps/search',
    ) {}

    public static function fromConfig(): self
    {
        return new self(
            apiKey: config('services.outscraper.key'),
            baseUrl: rtrim((string) config('services.outscraper.base_url', 'https://api.outscraper.com'), '/'),
            timeout: (int) config('services.outscraper.timeout', 60),
            retries: (int) config('services.outscraper.retries', 2),
            language: (string) config('services.outscraper.language', 'es'),
            region: (string) config('services.outscraper.region', 'ES'),
        );
    }

    public function fetchPlaces(array $queries, int $limitPerQuery = 1): array
    {
        $queries = array_values(array_filter(array_map(
            static fn ($q) => trim((string) $q),
            $queries
        ), static fn (string $q) => $q !== ''));

        if ($queries === []) {
            return [];
        }

        if ($this->apiKey === null || $this->apiKey === '') {
            throw new RuntimeException('OUTSCRAPER_API_KEY no configurada.');
        }

        if (count($queries) > 1000) {
            throw new RuntimeException('Outscraper admite como máximo 1000 queries por petición.');
        }

        $url = $this->baseUrl.$this->endpoint;
        $queryString = $this->buildQueryString($queries, $limitPerQuery);

        try {
            $response = Http::withHeaders([
                'X-API-KEY' => $this->apiKey,
            ])
                ->timeout($this->timeout)
                ->retry(max(0, $this->retries), 250, throw: false)
                ->get($url.'?'.$queryString);
        } catch (RequestException $e) {
            throw new RuntimeException('Error HTTP Outscraper: '.$e->getMessage(), 0, $e);
        }

        if (! $response->successful()) {
            throw new RuntimeException(
                'Outscraper respondió HTTP '.$response->status().': '.mb_substr($response->body(), 0, 300)
            );
        }

        $payload = $response->json();
        if (! is_array($payload)) {
            throw new RuntimeException('Respuesta Outscraper no es JSON válido.');
        }

        $status = $payload['status'] ?? null;
        if ($status !== null && ! in_array($status, ['Success', 'Pending'], true)) {
            throw new RuntimeException('Outscraper status inesperado: '.(string) $status);
        }

        // async=false debería devolver Success con data; Pending no debería llegar aquí.
        if (($payload['status'] ?? null) === 'Pending') {
            throw new RuntimeException('Outscraper devolvió Pending con async=false; reintentar más tarde.');
        }

        return $this->mapResults($queries, $payload['data'] ?? null);
    }

    /**
     * Outscraper espera query=a&query=b (claves repetidas), no query[0]=a.
     *
     * @param  list<string>  $queries
     */
    private function buildQueryString(array $queries, int $limitPerQuery): string
    {
        $parts = [
            'limit='.max(1, $limitPerQuery),
            'async=false',
            'language='.rawurlencode($this->language),
            'region='.rawurlencode($this->region),
        ];

        foreach ($queries as $query) {
            $parts[] = 'query='.rawurlencode($query);
        }

        return implode('&', $parts);
    }

    /**
     * @param  list<string>  $queries
     * @return array<string, PlaceMetrics|null>
     */
    private function mapResults(array $queries, mixed $data): array
    {
        $result = [];
        foreach ($queries as $query) {
            $result[$query] = null;
        }

        if (! is_array($data)) {
            return $result;
        }

        // Forma habitual: data = [ [place, …], [place, …] ] alineado con cada query.
        foreach ($queries as $index => $query) {
            $bucket = $data[$index] ?? null;
            if (! is_array($bucket) || $bucket === []) {
                continue;
            }

            // A veces un bucket es una ficha asociativa directa.
            $first = array_is_list($bucket) ? ($bucket[0] ?? null) : $bucket;
            if (! is_array($first) || $first === []) {
                continue;
            }

            $result[$query] = PlaceMetrics::fromOutscraperPlace($query, $first);
        }

        return $result;
    }
}
