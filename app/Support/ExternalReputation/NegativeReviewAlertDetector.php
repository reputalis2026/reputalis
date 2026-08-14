<?php

namespace App\Support\ExternalReputation;

use App\Models\ClientExternalReputationAlert;
use App\Models\ClientExternalReputationSnapshot;

/**
 * Decide si hay alerta entre dos snapshots.
 *
 * - negative_increase: suben 1★ o 2★ (prioridad).
 * - total_drop: baja el total sin subida de 1★/2★ (posible borrado; anomalía, no falsa alerta negativa).
 * - null: sin alerta (p. ej. solo baja el total ya cubierto, o sin cambios relevantes).
 */
class NegativeReviewAlertDetector
{
    /**
     * @return array{
     *   kind: string,
     *   delta_stars_1: int,
     *   delta_stars_2: int,
     *   delta_reviews_total: int
     * }|null
     */
    public function detect(
        ?ClientExternalReputationSnapshot $previous,
        ClientExternalReputationSnapshot $current,
    ): ?array {
        if ($previous === null) {
            return null;
        }

        $delta1 = max(0, (int) $current->stars_1 - (int) $previous->stars_1);
        $delta2 = max(0, (int) $current->stars_2 - (int) $previous->stars_2);
        $deltaTotal = (int) $current->reviews_total - (int) $previous->reviews_total;

        // Prioridad: nuevas reseñas malas.
        if ($delta1 > 0 || $delta2 > 0) {
            return [
                'kind' => ClientExternalReputationAlert::KIND_NEGATIVE_INCREASE,
                'delta_stars_1' => $delta1,
                'delta_stars_2' => $delta2,
                'delta_reviews_total' => $deltaTotal,
            ];
        }

        // Anomalía: el total baja sin que hayan subido 1★/2★ (posible borrado en Google).
        // No se trata como alerta de reseñas negativas.
        if ($deltaTotal < 0) {
            return [
                'kind' => ClientExternalReputationAlert::KIND_TOTAL_DROP,
                'delta_stars_1' => 0,
                'delta_stars_2' => 0,
                'delta_reviews_total' => $deltaTotal,
            ];
        }

        return null;
    }
}
