<?php

return [
    /*
    |--------------------------------------------------------------------------
    | ARES API URL
    |--------------------------------------------------------------------------
    */
    'api_url' => (string) env('ARES_API_URL', 'https://ares.gov.cz/ekonomicke-subjekty-v-be/rest'),

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    |
    | Successful lookups are cached to reduce load on the ARES API. Set
    | "enabled" to false (or ARES_CACHE_ENABLED=false) to turn caching off
    | completely — every lookup will then hit the ARES API directly.
    |
    */
    'cache' => [
        'enabled' => (bool) env('ARES_CACHE_ENABLED', true),
        'ttl' => (int) env('ARES_CACHE_TTL', 86400),
        'store' => env('ARES_CACHE_STORE'),
        'prefix' => (string) env('ARES_CACHE_PREFIX', 'ares:v1:company:'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging channel
    |--------------------------------------------------------------------------
    */
    'log_channel' => (string) env('ARES_LOG_CHANNEL', 'stack'),

    /*
    |--------------------------------------------------------------------------
    | Guzzle HTTP options
    |--------------------------------------------------------------------------
    */
    'http_options' => [
        'timeout' => (float) env('ARES_HTTP_TIMEOUT', 5.0),
        'connect_timeout' => (float) env('ARES_HTTP_CONNECT_TIMEOUT', 3.0),
    ],

    /*
    |--------------------------------------------------------------------------
    | Subject Indexing
    |--------------------------------------------------------------------------
    */
    'indexing' => [
        'enabled' => (bool) env('ARES_INDEXING_ENABLED', true),
        'auto_index' => (bool) env('ARES_AUTO_INDEX', true),
        'stale_days' => (int) env('ARES_STALE_DAYS', 30),
        'queue' => env('ARES_INDEX_QUEUE'),
        'connection' => env('ARES_INDEX_CONNECTION'),
    ],
];
