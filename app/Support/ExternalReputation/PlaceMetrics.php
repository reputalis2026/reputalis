<?php

namespace App\Support\ExternalReputation;

/**
 * Métricas agregadas de una ficha Google Maps (sin textos ni autores de reseñas).
 */
final class PlaceMetrics
{
    /**
     * @param  array{1: int, 2: int, 3: int, 4: int, 5: int}  $stars
     * @param  array<string, mixed>|null  $raw
     */
    public function __construct(
        public readonly string $query,
        public readonly ?string $placeId,
        public readonly ?string $googleId,
        public readonly ?string $name,
        public readonly ?float $rating,
        public readonly int $reviewsTotal,
        public readonly array $stars,
        public readonly ?array $raw = null,
    ) {}

    /**
     * @param  array<string, mixed>  $place
     */
    public static function fromOutscraperPlace(string $query, array $place): self
    {
        $stars = self::parseStars($place);
        $reviewsTotal = (int) ($place['reviews'] ?? array_sum($stars));
        $rating = isset($place['rating']) && $place['rating'] !== null && $place['rating'] !== ''
            ? (float) $place['rating']
            : null;

        return new self(
            query: $query,
            placeId: isset($place['place_id']) ? (string) $place['place_id'] : null,
            googleId: isset($place['google_id']) ? (string) $place['google_id'] : null,
            name: isset($place['name']) ? (string) $place['name'] : null,
            rating: $rating,
            reviewsTotal: $reviewsTotal,
            stars: $stars,
            raw: self::sanitizeRaw($place),
        );
    }

    /**
     * @param  array<string, mixed>  $place
     * @return array{1: int, 2: int, 3: int, 4: int, 5: int}
     */
    public static function parseStars(array $place): array
    {
        $stars = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];

        $perScore = $place['reviews_per_score'] ?? null;
        if (is_array($perScore)) {
            foreach ([1, 2, 3, 4, 5] as $i) {
                $key = (string) $i;
                if (array_key_exists($key, $perScore)) {
                    $stars[$i] = (int) $perScore[$key];
                } elseif (array_key_exists($i, $perScore)) {
                    $stars[$i] = (int) $perScore[$i];
                }
            }

            return $stars;
        }

        foreach ([1, 2, 3, 4, 5] as $i) {
            $column = 'reviews_per_score_'.$i;
            if (isset($place[$column])) {
                $stars[$i] = (int) $place[$column];
            }
        }

        return $stars;
    }

    /**
     * Conserva solo campos agregados útiles para depuración (sin enriquecer contactos).
     *
     * @param  array<string, mixed>  $place
     * @return array<string, mixed>
     */
    private static function sanitizeRaw(array $place): array
    {
        $keep = [
            'name',
            'place_id',
            'google_id',
            'rating',
            'reviews',
            'reviews_per_score',
            'reviews_per_score_1',
            'reviews_per_score_2',
            'reviews_per_score_3',
            'reviews_per_score_4',
            'reviews_per_score_5',
            'query',
        ];

        $out = [];
        foreach ($keep as $key) {
            if (array_key_exists($key, $place)) {
                $out[$key] = $place[$key];
            }
        }

        return $out;
    }

    public function hasStarsBreakdown(): bool
    {
        return array_sum($this->stars) > 0;
    }
}
