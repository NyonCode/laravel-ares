<?php

return [
    /*
    |--------------------------------------------------------------------------
    | ARES API URL
    |--------------------------------------------------------------------------
    */
    'api_url' => env('ARES_API_URL', 'https://ares.gov.cz/ekonomicke-subjekty-v-be/rest'),

    /*
    |--------------------------------------------------------------------------
    | Cache TTL (seconds)
    |--------------------------------------------------------------------------
    */
    'cache_ttl' => env('ARES_CACHE_TTL', 86400),

    /*
    |--------------------------------------------------------------------------
    | Logging channel
    |--------------------------------------------------------------------------
    */
    'log_channel' => env('ARES_LOG_CHANNEL', 'stack'),

    /*
    |--------------------------------------------------------------------------
    | Guzzle HTTP options
    |--------------------------------------------------------------------------
    */
    'http_options' => [
        'timeout' => env('ARES_HTTP_TIMEOUT', 5.0),
        'connect_timeout' => env('ARES_HTTP_CONNECT_TIMEOUT', 3.0),
    ],

    /*
    |--------------------------------------------------------------------------
    | Subject Indexing
    |--------------------------------------------------------------------------
    */
    'indexing' => [
        'enabled' => env('ARES_INDEXING_ENABLED', true),
        'auto_index' => env('ARES_AUTO_INDEX', true),
        'stale_days' => env('ARES_STALE_DAYS', 30),
        'queue' => env('ARES_INDEX_QUEUE'),
        'connection' => env('ARES_INDEX_CONNECTION'),
    ],
];
