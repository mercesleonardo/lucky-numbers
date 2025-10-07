<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Lottery Import Configuration
    |--------------------------------------------------------------------------
    |
    | Configurações para importação automática de dados de loterias
    |
    */

    'scheduling' => [
        /*
        |--------------------------------------------------------------------------
        | Horários de Importação
        |--------------------------------------------------------------------------
        */
        'daily_import_time'    => env('LOTTERY_DAILY_IMPORT_TIME', '08:00'),
        'weekly_gap_fill_time' => env('LOTTERY_WEEKLY_GAP_FILL_TIME', '02:00'),
        'midday_popular_time'  => env('LOTTERY_MIDDAY_POPULAR_TIME', '12:00'),

        /*
        |--------------------------------------------------------------------------
        | Configurações de Gap Fill
        |--------------------------------------------------------------------------
        */
        'gap_fill_days'         => env('LOTTERY_GAP_FILL_DAYS', 7),
        'gap_fill_max_contests' => env('LOTTERY_GAP_FILL_MAX_CONTESTS', 10),

        /*
        |--------------------------------------------------------------------------
        | Jogos Populares para Importação Frequente
        |--------------------------------------------------------------------------
        */
        'popular_games' => [
            'megasena',
            'lotofacil',
            'quina',
        ],

        /*
        |--------------------------------------------------------------------------
        | Notificações
        |--------------------------------------------------------------------------
        */
        'email_on_failure' => env('LOTTERY_EMAIL_ON_FAILURE', true),
        'admin_email'      => env('LOTTERY_ADMIN_EMAIL', env('MAIL_FROM_ADDRESS')),
    ],

    /*
    |--------------------------------------------------------------------------
    | API Configuration
    |--------------------------------------------------------------------------
    */
    'api' => [
        'base_url'       => env('LOTTERY_API_URL', 'https://loteriascaixa-api.herokuapp.com/api'),
        'timeout'        => env('LOTTERY_API_TIMEOUT', 30),
        'retry_attempts' => env('LOTTERY_API_RETRY_ATTEMPTS', 3),
        'retry_delay'    => env('LOTTERY_API_RETRY_DELAY', 100),
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Settings
    |--------------------------------------------------------------------------
    */
    'performance' => [
        'request_delay'         => env('LOTTERY_REQUEST_DELAY', 100000), // microseconds
        'background_processing' => env('LOTTERY_BACKGROUND_PROCESSING', true),
        'cache_available_games' => env('LOTTERY_CACHE_GAMES', true),
        'cache_ttl'             => env('LOTTERY_CACHE_TTL', 3600), // seconds
    ],
];
