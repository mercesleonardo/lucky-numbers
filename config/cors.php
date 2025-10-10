<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://localhost:3000',     // React/Next.js dev
        'http://localhost:5173',     // Vite dev
        'http://localhost:8080',     // Vue dev
        'http://127.0.0.1:3000',     // React/Next.js dev
        'http://127.0.0.1:5173',     // Vite dev
        'http://127.0.0.1:8080',     // Vue dev
        // Adicione suas URLs de produção aqui
    ],

    'allowed_origins_patterns' => [
        // Permite subdominios locais
        '/^http:\/\/localhost(:\d+)?$/',
        '/^http:\/\/127\.0\.0\.1(:\d+)?$/',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [
        'X-RateLimit-Limit',
        'X-RateLimit-Remaining',
        'Retry-After',
    ],

    'max_age' => 0,

    'supports_credentials' => false,

];
