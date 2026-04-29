<?php

return [
    'title' => 'Dashboard',
    'navigation_label' => 'Dashboard',
    'clients_overview' => [
        'heading' => 'Clientes',
        'tabs' => [
            'active' => 'Clientes activos',
            'inactive' => 'Clientes inactivos',
            'ending_soon' => 'Clientes baja próxima',
            'distributors' => 'Distribuidores',
        ],
        'columns' => [
            'client' => 'Cliente',
            'distributor' => 'Distribuidor',
            'start_date' => 'Fecha inicio',
            'end_date' => 'Fecha fin',
            'status' => 'Estado',
            'phone' => 'Teléfono',
            'surveys_today' => 'Encuestas hoy',
            'satisfied_percent' => '% satisfechos',
        ],
        'empty' => [
            'clients' => 'No hay clientes en esta sección.',
            'distributors' => 'No hay distribuidores.',
        ],
    ],
    'stats' => [
        'heading' => 'Métricas CSAT',
        'avg_score' => 'Nota media',
        'avg_score_description' => 'Promedio de puntuación',
        'total_surveys' => 'Encuestas totales',
        'total_surveys_description' => 'En el periodo seleccionado',
        'satisfied_percent' => '% satisfechos',
        'satisfied_percent_description' => 'Puntuación 4–5',
        'surveys_today' => 'Encuestas hoy',
        'surveys_today_description' => 'Creadas hoy',
    ],
];
