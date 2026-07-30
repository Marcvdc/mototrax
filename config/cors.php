<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Bepaalt welke cross-origin verzoeken de API mag beantwoorden. De
    | toegestane origins zijn env-gedreven (CORS_ALLOWED_ORIGINS, komma-
    | gescheiden); default '*'. Zet in productie expliciete origins.
    | De API is token-based (Sanctum bearer), dus supports_credentials=false.
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => explode(',', env('CORS_ALLOWED_ORIGINS', '*')),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
