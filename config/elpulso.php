<?php

return [

    /*
    | Mínimo de encuestas para entrar en la clasificación del equipo.
    | La guía aprobada deja este umbral configurable (por defecto 10).
    */
    'min_sample' => (int) env('ELPULSO_MIN_SAMPLE', 10),

    'splash_ms' => 4800,

    'timezone' => 'Europe/Madrid',

];
