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

    /**
     * Tramo del arco «progreso hacia el objetivo» a partir de la nota real.
     *
     * La décima visible es el redondeo a 1 decimal. Si el valor exacto queda por
     * debajo de esa décima (redondeó hacia arriba), el arco va de la décima
     * anterior a la siguiente (4,46 → 4,5 visible → 4,4–4,6). Si no, va de esa
     * décima a la siguiente (4,43 → 4,4 visible → 4,4–4,5). El techo es 5,0.
     *
     * @return array{from: float, to: float, percent: int}
     */
    public static function objectiveProgress(float $rawReal): array
    {
        $raw = round($rawReal, 4);

        if ($raw >= 5) {
            return ['from' => 5.0, 'to' => 5.0, 'percent' => 100];
        }

        if ($raw < 0) {
            $raw = 0.0;
        }

        $displayed = round($raw, 1);

        if ($raw < $displayed - 0.00001) {
            $from = round($displayed - 0.1, 1);
            $to = round(min(5.0, $displayed + 0.1), 1);
        } else {
            $from = $displayed;
            $to = round(min(5.0, $displayed + 0.1), 1);
        }

        if ($from < 0) {
            $from = 0.0;
        }

        if ($to <= $from) {
            return ['from' => $from, 'to' => $from, 'percent' => 100];
        }

        $ratio = ($raw - $from) / ($to - $from);
        $percent = (int) round($ratio * 100);
        $percent = min(100, max(0, (int) (floor($percent / 5) * 5)));

        return [
            'from' => $from,
            'to' => $to,
            'percent' => $percent,
        ];
    }
}
