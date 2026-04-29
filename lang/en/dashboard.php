<?php

return [
    'title' => 'Dashboard',
    'navigation_label' => 'Dashboard',
    'clients_overview' => [
        'heading' => 'Clients',
        'tabs' => [
            'active' => 'Active clients',
            'inactive' => 'Inactive clients',
            'ending_soon' => 'Clients ending soon',
            'distributors' => 'Distributors',
        ],
        'columns' => [
            'client' => 'Client',
            'distributor' => 'Distributor',
            'start_date' => 'Start date',
            'end_date' => 'End date',
            'status' => 'Status',
            'phone' => 'Phone',
            'surveys_today' => 'Surveys today',
            'satisfied_percent' => '% satisfied',
        ],
        'empty' => [
            'clients' => 'There are no clients in this section.',
            'distributors' => 'There are no distributors.',
        ],
    ],
    'stats' => [
        'heading' => 'CSAT metrics',
        'avg_score' => 'Average score',
        'avg_score_description' => 'Average rating',
        'total_surveys' => 'Total surveys',
        'total_surveys_description' => 'In the selected period',
        'satisfied_percent' => '% satisfied',
        'satisfied_percent_description' => 'Score 4–5',
        'surveys_today' => 'Surveys today',
        'surveys_today_description' => 'Created today',
    ],
];
