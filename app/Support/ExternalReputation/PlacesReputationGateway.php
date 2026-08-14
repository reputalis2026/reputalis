<?php

namespace App\Support\ExternalReputation;

interface PlacesReputationGateway
{
    /**
     * Consulta una o varias fichas (place_id, google_id, URL o texto).
     *
     * @param  list<string>  $queries
     * @return array<string, PlaceMetrics|null> mapa query => métricas (null si no hubo resultado)
     */
    public function fetchPlaces(array $queries, int $limitPerQuery = 1): array;
}
