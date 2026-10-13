<?php

namespace App\Support\Pulse;

use App\Models\Client;
use Carbon\Carbon;

/**
 * Ranking de demostración para Comparativa sector en El Pulso.
 * Misma estructura que la maqueta, con cifras estables por farmacia.
 */
final class FakePulseSector
{
    /**
     * @return array<string, mixed>
     */
    public function forClient(Client $client): array
    {
        return $this->build(
            (string) $client->id,
            trim((string) ($client->codigo_postal ?? '')),
            trim((string) ($client->ciudad ?? '')),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function build(string $clientId, string $postalCode, string $city): array
    {
        $seed = crc32($clientId) & 0x7fffffff;
        $yourReviews = 168 + ($seed % 24);
        $ranked = $this->ranking($seed, $yourReviews);
        $you = collect($ranked)->firstWhere('is_you', true);
        $position = (int) $you['position'];
        $analyzed = count($ranked);
        $average = round(collect($ranked)->avg('rating'), 2);
        $yourRating = (float) $you['rating'];
        $buckets = $this->histogram($ranked, $yourRating);
        $yourBucket = (int) round(($yourRating - 3.5) * 10);

        $from = max(1, $position - 2);
        $to = min($analyzed, $position + 2);
        $nearby = array_values(array_filter(
            $ranked,
            fn (array $row): bool => $row['position'] >= $from && $row['position'] <= $to
        ));

        $cityTotal = 92 + ($seed % 14);
        $cityPosition = 10 + ($seed % 4);
        $provinceTotal = 312 + ($seed % 28);
        $provincePosition = 19 + ($seed % 6);

        return [
            'postal_code' => $postalCode,
            'city' => $city,
            'province' => $this->provinceFromPostal($postalCode),
            'position' => $position,
            'analyzed' => $analyzed,
            'ahead' => $position - 1,
            'behind' => $analyzed - $position,
            'your_rating' => $yourRating,
            'your_reviews' => (int) $you['reviews'],
            'average' => $average,
            'projection_stars' => 8 + ($seed % 5),
            'projection_position' => max(1, $position - 1),
            'histogram' => $buckets,
            'average_left' => $this->axisPercent($average),
            'you_left' => (($yourBucket + 0.5) / 16) * 100,
            'nearby' => $nearby,
            'ranking' => $ranked,
            'evolution' => $this->evolution($position),
            'territories' => [
                [
                    'kind' => 'Ciudad',
                    'name' => $city !== '' ? $city : '—',
                    'position' => $cityPosition,
                    'ahead' => $cityPosition - 1,
                    'behind' => $cityTotal - $cityPosition,
                    'analyzed' => $cityTotal,
                ],
                [
                    'kind' => 'Provincia',
                    'name' => $this->provinceFromPostal($postalCode) ?? '—',
                    'position' => $provincePosition,
                    'ahead' => $provincePosition - 1,
                    'behind' => $provinceTotal - $provincePosition,
                    'analyzed' => $provinceTotal,
                ],
            ],
        ];
    }

    /**
     * @return list<array{name: string, rating: float, reviews: int, is_you: bool, position: int}>
     */
    private function ranking(int $seed, int $yourReviews): array
    {
        $names = [
            'Alcazaba', 'Puerto', 'Mercado', 'Castillo', 'Olivo', 'Faro', 'Ribera', 'Jardín',
            'Estación', 'Carmen', 'Rosario', 'Nueva', 'Mayor', 'Real', 'Huerta', 'Palmera',
            'Dunas', 'Avenida', 'Sol', 'Central', 'Mar', 'Luz', 'Plaza', 'Levante', 'Parque',
            'Rambla', 'Norte',
        ];
        $shift = $seed % count($names);
        $names = array_merge(array_slice($names, $shift), array_slice($names, 0, $shift));

        $groups = [
            [4.7, [248]],
            [4.6, [302, 221]],
            [4.5, [$yourReviews, 154, 128, 101, 77, 58, 36]],
            [4.4, [388, 264, 176, 141, 98]],
            [4.3, [240, 188, 132, 90]],
            [4.2, [210, 156, 84]],
            [4.1, [174, 96]],
            [4.0, [220, 70]],
            [3.9, [64]],
            [3.8, [48]],
        ];

        $rows = [];
        $nameIndex = 0;
        foreach ($groups as [$rating, $reviews]) {
            foreach ($reviews as $index => $count) {
                $isYou = $rating === 4.5 && $index === 0;
                $rows[] = [
                    'name' => $isYou ? 'Tu farmacia' : 'Farmacia '.$names[$nameIndex++],
                    'rating' => $rating,
                    'reviews' => $count,
                    'is_you' => $isYou,
                ];
            }
        }

        usort($rows, static function (array $a, array $b): int {
            if ($a['rating'] === $b['rating']) {
                return $b['reviews'] <=> $a['reviews'];
            }

            return $b['rating'] <=> $a['rating'];
        });

        foreach ($rows as $index => &$row) {
            $row['position'] = $index + 1;
        }
        unset($row);

        return $rows;
    }

    /**
     * @param  list<array{rating: float, is_you: bool}>  $ranked
     * @return list<array{rating: float, count: int, yours: bool, height: float}>
     */
    private function histogram(array $ranked, float $yourRating): array
    {
        $counts = [];
        for ($step = 35; $step <= 50; $step++) {
            $counts[number_format($step / 10, 1, '.', '')] = 0;
        }

        $yourBucket = number_format(round($yourRating, 1), 1, '.', '');
        foreach ($ranked as $row) {
            $bucket = number_format(round($row['rating'], 1), 1, '.', '');
            if (isset($counts[$bucket])) {
                $counts[$bucket]++;
            }
        }

        $max = max(1, ...array_values($counts));
        $bars = [];
        foreach ($counts as $rating => $count) {
            $bars[] = [
                'rating' => (float) $rating,
                'count' => $count,
                'yours' => $rating === $yourBucket,
                'height' => $count > 0 ? max(12, ($count / $max) * 100) : 0,
            ];
        }

        return $bars;
    }

    /**
     * @return list<array{label: string, position: int, height: float}>
     */
    private function evolution(int $position): array
    {
        $end = Carbon::now('Europe/Madrid')->startOfMonth();
        $rows = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = $end->copy()->subMonths($i);
            $rank = $position + $i;
            $rows[] = [
                'label' => PulseFormat::monthShort($month),
                'position' => $rank,
                'height' => 46 + ((5 - $i) * 10),
            ];
        }

        return $rows;
    }

    private function axisPercent(float $rating): float
    {
        return max(0, min(100, (($rating - 3.5) / 1.5) * 100));
    }

    private function provinceFromPostal(string $postalCode): ?string
    {
        $prefix = substr($postalCode, 0, 2);
        $provinces = [
            '01' => 'Álava', '02' => 'Albacete', '03' => 'Alicante', '04' => 'Almería',
            '05' => 'Ávila', '06' => 'Badajoz', '07' => 'Baleares', '08' => 'Barcelona',
            '09' => 'Burgos', '10' => 'Cáceres', '11' => 'Cádiz', '12' => 'Castellón',
            '13' => 'Ciudad Real', '14' => 'Córdoba', '15' => 'A Coruña', '16' => 'Cuenca',
            '17' => 'Girona', '18' => 'Granada', '19' => 'Guadalajara', '20' => 'Gipuzkoa',
            '21' => 'Huelva', '22' => 'Huesca', '23' => 'Jaén', '24' => 'León',
            '25' => 'Lleida', '26' => 'La Rioja', '27' => 'Lugo', '28' => 'Madrid',
            '29' => 'Málaga', '30' => 'Murcia', '31' => 'Navarra', '32' => 'Ourense',
            '33' => 'Asturias', '34' => 'Palencia', '35' => 'Las Palmas', '36' => 'Pontevedra',
            '37' => 'Salamanca', '38' => 'Santa Cruz de Tenerife', '39' => 'Cantabria', '40' => 'Segovia',
            '41' => 'Sevilla', '42' => 'Soria', '43' => 'Tarragona', '44' => 'Teruel',
            '45' => 'Toledo', '46' => 'Valencia', '47' => 'Valladolid', '48' => 'Bizkaia',
            '49' => 'Zamora', '50' => 'Zaragoza', '51' => 'Ceuta', '52' => 'Melilla',
        ];

        return $provinces[$prefix] ?? null;
    }
}
