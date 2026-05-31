<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Allowed origins must include every domain that runs the Vue frontend.
    | In production, replace the localhost entries with your real domain.
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://localhost:5173',   // Vite dev server
        'http://localhost:5174',   // Vite fallback port
        'http://localhost:3000',   // Alternative dev port
        'http://127.0.0.1:5173',
        'http://127.0.0.1:8080',
        env('FRONTEND_URL', 'http://localhost:5173'),
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => [
        'Content-Type',
        'Accept',
        'Authorization',
        'X-Requested-With',
        'X-Windows-User',      // Used for local Windows Auth simulation
        'Accept-Language',
        'Origin',
        'Cache-Control',
    ],

    'exposed_headers' => [],

    'max_age' => 3600,

    /*
    | Must be TRUE when the frontend uses:
    |   axios: withCredentials: true
    |   fetch: credentials: 'include'
    | This is required for Windows NTLM/Kerberos auth and Sanctum cookies.
    */
    'supports_credentials' => true,

];
