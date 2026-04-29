<?php

return [
    'title' => 'Dashboard',
    'navigation_label' => 'Dashboard',
    'clients_overview' => [
        'heading' => 'Clientes',
        'tabs' => [
            'active' => 'Clientes ativos',
            'inactive' => 'Clientes inativos',
            'ending_soon' => 'Clientes perto do fim',
            'distributors' => 'Distribuidores',
        ],
        'columns' => [
            'client' => 'Cliente',
            'distributor' => 'Distribuidor',
            'start_date' => 'Data de início',
            'end_date' => 'Data de fim',
            'status' => 'Estado',
            'phone' => 'Telefone',
            'surveys_today' => 'Inquéritos hoje',
            'satisfied_percent' => '% satisfeitos',
        ],
        'empty' => [
            'clients' => 'Não há clientes nesta secção.',
            'distributors' => 'Não há distribuidores.',
        ],
    ],
    'stats' => [
        'heading' => 'Métricas CSAT',
        'avg_score' => 'Nota média',
        'avg_score_description' => 'Média da pontuação',
        'total_surveys' => 'Inquéritos totais',
        'total_surveys_description' => 'No período selecionado',
        'satisfied_percent' => '% satisfeitos',
        'satisfied_percent_description' => 'Pontuação 4–5',
        'surveys_today' => 'Inquéritos hoje',
        'surveys_today_description' => 'Criados hoje',
    ],
];
