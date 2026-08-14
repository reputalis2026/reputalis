<?php

namespace App\Support\ExternalReputation;

use App\Models\ClientExternalReputationSnapshot;

/**
 * Cálculos de puntuación propia y proyección a objetivo (estimación, no garantía Google).
 */
final class RatingProjection
{
    public const DEFAULT_TARGET = 4.5;

    /**
     * @param  array{1?: int, 2?: int, 3?: int, 4?: int, 5?: int}  $stars
     */
    public static function calculatedRating(array $stars): ?float
    {
        return ClientExternalReputationSnapshot::calculateRatingFromStars($stars);
    }

    /**
     * @param  array{1?: int, 2?: int, 3?: int, 4?: int, 5?: int}  $stars
     */
    public static function fiveStarsNeededForTarget(array $stars, float $target = self::DEFAULT_TARGET): ?int
    {
        return ClientExternalReputationSnapshot::fiveStarsNeededForTarget($stars, $target);
    }
}
