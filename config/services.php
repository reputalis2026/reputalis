<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'outscraper' => [
        // Sin API key → fake por defecto (desarrollo). Con key → http salvo que se fuerce fake.
        'driver' => env('OUTSCRAPER_DRIVER', env('OUTSCRAPER_API_KEY') ? 'http' : 'fake'),
        'key' => env('OUTSCRAPER_API_KEY'),
        'base_url' => env('OUTSCRAPER_BASE_URL', 'https://api.outscraper.cloud'),
        'timeout' => (int) env('OUTSCRAPER_TIMEOUT', 60),
        'retries' => (int) env('OUTSCRAPER_RETRIES', 2),
        'language' => env('OUTSCRAPER_LANGUAGE', 'es'),
        'region' => env('OUTSCRAPER_REGION', 'ES'),
        'endpoint' => env('OUTSCRAPER_ENDPOINT', '/google-maps-search'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
