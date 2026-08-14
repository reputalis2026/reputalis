<?php

namespace App\Support\ExternalReputation;

/**
 * Cliente falso para desarrollo/tests sin cuenta Outscraper.
 * Devuelve métricas deterministas derivadas del query.
 */
class FakeOutscraperPlacesClient implements PlacesReputationGateway
{
    public function fetchPlaces(array $queries, int $limitPerQuery = 1): array
    {
        $out = [];

        foreach ($queries as $query) {
            $query = trim((string) $query);
            if ($query === '') {
                continue;
            }

            $hash = crc32($query);
            $base = 50 + ($hash % 200);
            $stars = [
                1 => (int) max(0, ($hash % 7)),
                2 => (int) max(0, (($hash >> 3) % 5)),
                3 => (int) max(1, (($hash >> 6) % 15)),
                4 => (int) max(5, (($hash >> 9) % 40)),
                5 => (int) max(20, $base),
            ];
            $total = array_sum($stars);
            $weighted = 1 * $stars[1] + 2 * $stars[2] + 3 * $stars[3] + 4 * $stars[4] + 5 * $stars[5];
            $rating = round($weighted / $total, 1);

            $out[$query] = new PlaceMetrics(
                query: $query,
                placeId: str_starts_with($query, 'ChIJ') ? $query : 'ChIJFake'.substr(md5($query), 0, 20),
                googleId: '0xfake:'.dechex($hash),
                name: 'Fake place '.$query,
                rating: $rating,
                reviewsTotal: $total,
                stars: $stars,
                raw: [
                    'name' => 'Fake place '.$query,
                    'rating' => $rating,
                    'reviews' => $total,
                    'reviews_per_score' => [
                        '1' => $stars[1],
                        '2' => $stars[2],
                        '3' => $stars[3],
                        '4' => $stars[4],
                        '5' => $stars[5],
                    ],
                    'fake' => true,
                ],
            );
        }

        return $out;
    }
}
