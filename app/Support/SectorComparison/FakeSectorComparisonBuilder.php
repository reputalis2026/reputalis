<?php

namespace App\Support\SectorComparison;

use App\Models\Client;

/**
 * Datos fake de comparativa sector para maquetar la UI sin Outscraper.
 * Determinista por client id + ámbito. Valores distintos del mockup de diseño.
 */
class FakeSectorComparisonBuilder
{
    /**
     * @return array{
     *   fake: bool,
     *   heading: string,
     *   subheading: string,
     *   scopes: array<string, array<string, mixed>>
     * }
     */
    public function build(Client $client): array
    {
        $postal = trim((string) ($client->codigo_postal ?? '')) ?: '28014';
        $city = trim((string) ($client->ciudad ?? '')) ?: 'Madrid';
        $province = $this->guessProvince($city, $postal);

        $seed = crc32((string) $client->id) ^ 0x5a17c3;

        return [
            'fake' => true,
            'heading' => __('client.dashboard.sector.heading'),
            'subheading' => __('client.dashboard.sector.subheading'),
            'scopes' => [
                'postal' => $this->buildScope(
                    key: 'postal',
                    title: __('client.dashboard.sector.scope_postal', ['code' => $postal]),
                    averageLabel: __('client.dashboard.sector.kpi_average_postal'),
                    chartType: 'scatter',
                    seed: $seed + 17,
                    count: 18,
                    yourRating: 4.6,
                    yourReviews: 124,
                    yourName: (string) ($client->namecommercial ?: __('client.dashboard.sector.your_pharmacy')),
                    startRank: 9,
                    endRank: 5,
                    showProjection: true,
                    projectionStars: 9,
                ),
                'city' => $this->buildScope(
                    key: 'city',
                    title: __('client.dashboard.sector.scope_city', ['name' => $city]),
                    averageLabel: __('client.dashboard.sector.kpi_average_city'),
                    chartType: 'histogram',
                    seed: $seed + 41,
                    count: 112,
                    yourRating: 4.6,
                    yourReviews: 124,
                    yourName: (string) ($client->namecommercial ?: __('client.dashboard.sector.your_pharmacy')),
                    startRank: 28,
                    endRank: 19,
                    showProjection: false,
                    projectionStars: 0,
                ),
                'province' => $this->buildScope(
                    key: 'province',
                    title: __('client.dashboard.sector.scope_province', ['name' => $province]),
                    averageLabel: __('client.dashboard.sector.kpi_average_province'),
                    chartType: 'histogram',
                    seed: $seed + 73,
                    count: 286,
                    yourRating: 4.6,
                    yourReviews: 124,
                    yourName: (string) ($client->namecommercial ?: __('client.dashboard.sector.your_pharmacy')),
                    startRank: 64,
                    endRank: 41,
                    showProjection: false,
                    projectionStars: 0,
                ),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildScope(
        string $key,
        string $title,
        string $averageLabel,
        string $chartType,
        int $seed,
        int $count,
        float $yourRating,
        int $yourReviews,
        string $yourName,
        int $startRank,
        int $endRank,
        bool $showProjection,
        int $projectionStars,
    ): array {
        $competitors = $this->generateCompetitors($seed, $count, $yourRating, $yourReviews, $yourName);
        $ranked = $this->rankCompetitors($competitors);
        $you = collect($ranked)->firstWhere('is_you', true);
        $position = (int) ($you['position'] ?? 1);
        $ahead = max(0, $position - 1);
        $behind = max(0, $count - $position);
        $average = round(collect($ranked)->avg('rating'), 2);

        $projection = null;
        if ($showProjection && $ahead > 0 && $projectionStars > 0) {
            $targetPosition = max(1, (int) ceil($position / 2));
            $projection = [
                'five_stars' => $projectionStars,
                'target_position' => $targetPosition,
            ];
        }

        $evolution = $this->buildEvolution($startRank, $endRank, $count);

        return [
            'key' => $key,
            'title' => $title,
            'average_label' => $averageLabel,
            'chart_type' => $chartType,
            'your_rating' => $yourRating,
            'your_reviews' => $yourReviews,
            'market_average' => $average,
            'analyzed' => $count,
            'position' => $position,
            'ahead' => $ahead,
            'behind' => $behind,
            'projection' => $projection,
            'competitors' => $ranked,
            'charts' => [
                'scatter' => [
                    'average' => $average,
                    'points' => array_map(static fn (array $row): array => [
                        'x' => $row['rating'],
                        'y' => $row['reviews'],
                        'name' => $row['name'],
                        'position' => $row['position'],
                        'is_you' => $row['is_you'],
                    ], $ranked),
                    'youLabel' => __('client.dashboard.sector.you_label'),
                    'averageLabel' => __('client.dashboard.sector.average_short'),
                    'xLabel' => __('client.dashboard.sector.axis_rating'),
                    'yLabel' => __('client.dashboard.sector.axis_reviews'),
                ],
                'histogram' => $this->buildHistogram($ranked, $yourRating, $average),
                'evolution' => [
                    'labels' => array_column($evolution, 'label'),
                    'positions' => array_column($evolution, 'position'),
                    'analyzed' => $count,
                    'seriesLabel' => __('client.dashboard.sector.evolution_series'),
                    'pointSuffix' => __('client.dashboard.sector.position_of', [
                        'position' => ':pos',
                        'total' => $count,
                    ]),
                ],
            ],
        ];
    }

    /**
     * @return list<array{name: string, rating: float, reviews: int, is_you: bool}>
     */
    private function generateCompetitors(
        int $seed,
        int $count,
        float $yourRating,
        int $yourReviews,
        string $yourName,
    ): array {
        $rows = [];
        $rng = $seed;

        for ($i = 0; $i < $count - 1; $i++) {
            $rng = ($rng * 1103515245 + 12345) & 0x7fffffff;
            $rating = round(3.8 + (($rng % 105) / 100), 2);
            $rng = ($rng * 1103515245 + 12345) & 0x7fffffff;
            $reviews = 12 + ($rng % 310);
            $rows[] = [
                'name' => __('client.dashboard.sector.competitor_name', ['n' => $i + 1]),
                'rating' => min(5.0, $rating),
                'reviews' => $reviews,
                'is_you' => false,
            ];
        }

        $rows[] = [
            'name' => $yourName,
            'rating' => $yourRating,
            'reviews' => $yourReviews,
            'is_you' => true,
        ];

        return $rows;
    }

    /**
     * @param  list<array{name: string, rating: float, reviews: int, is_you: bool}>  $competitors
     * @return list<array{name: string, rating: float, reviews: int, is_you: bool, position: int}>
     */
    private function rankCompetitors(array $competitors): array
    {
        usort($competitors, static function (array $a, array $b): int {
            if ($a['rating'] === $b['rating']) {
                return $b['reviews'] <=> $a['reviews'];
            }

            return $b['rating'] <=> $a['rating'];
        });

        $position = 1;
        foreach ($competitors as &$row) {
            $row['position'] = $position++;
        }
        unset($row);

        return $competitors;
    }

    /**
     * @param  list<array{rating: float, is_you?: bool}>  $ranked
     * @return array{categories: list<string>, counts: list<int>, your_rating: float, average: float, youLabel: string, averageLabel: string}
     */
    private function buildHistogram(array $ranked, float $yourRating, float $average): array
    {
        $buckets = [];
        for ($r = 38; $r <= 50; $r++) {
            $key = number_format($r / 10, 1, '.', '');
            $buckets[$key] = 0;
        }

        foreach ($ranked as $row) {
            $bucket = number_format(round($row['rating'] * 10) / 10, 1, '.', '');
            if (! isset($buckets[$bucket])) {
                $buckets[$bucket] = 0;
            }
            $buckets[$bucket]++;
        }

        ksort($buckets, SORT_NUMERIC);

        return [
            'categories' => array_keys($buckets),
            'counts' => array_values($buckets),
            'your_rating' => $yourRating,
            'average' => $average,
            'youLabel' => __('client.dashboard.sector.you_label'),
            'averageLabel' => __('client.dashboard.sector.average_short'),
        ];
    }

    /**
     * @return list<array{label: string, position: int}>
     */
    private function buildEvolution(int $startRank, int $endRank, int $analyzed): array
    {
        $months = [
            __('client.dashboard.sector.months.mar'),
            __('client.dashboard.sector.months.apr'),
            __('client.dashboard.sector.months.may'),
            __('client.dashboard.sector.months.jun'),
            __('client.dashboard.sector.months.jul'),
            __('client.dashboard.sector.months.aug'),
        ];

        $steps = count($months) - 1;
        $rows = [];
        $wiggle = [0, 1, -1, 0, -1, 0];

        for ($i = 0; $i < count($months); $i++) {
            $t = $steps > 0 ? $i / $steps : 1;
            $pos = (int) round($startRank + ($endRank - $startRank) * $t) + ($wiggle[$i] ?? 0);
            $rows[] = [
                'label' => $months[$i],
                'position' => max(1, min($analyzed, $pos)),
            ];
        }

        return $rows;
    }

    private function guessProvince(string $city, string $postal): string
    {
        if ($city !== '') {
            return $city;
        }

        if (str_starts_with($postal, '28')) {
            return 'Madrid';
        }

        if (str_starts_with($postal, '04')) {
            return 'Almería';
        }

        return __('client.dashboard.sector.province_fallback');
    }
}
