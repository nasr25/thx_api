<?php

return [
    'paths'                    => ['api/*'],
    'allowed_methods'          => ['*'],

    // Allow the configured frontend URL plus common local dev ports, so the
    // app still works if Vite picks a different port (5174, 5175, ...).
    'allowed_origins'          => array_values(array_unique(array_filter([
        env('FRONTEND_URL', 'http://localhost:5173'),
        'http://localhost:5173',
        'http://localhost:5174',
        'http://localhost:5175',
        'http://127.0.0.1:5173',
        'http://127.0.0.1:5174',
    ]))),

    // Also allow any localhost / 127.0.0.1 port via pattern (dev convenience).
    'allowed_origins_patterns' => [
        '#^http://(localhost|127\.0\.0\.1):\d+$#',
    ],

    'allowed_headers'          => ['*'],
    'exposed_headers'          => [],
    'max_age'                  => 0,
    'supports_credentials'     => false,
];
