<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Windows Authentication diagnostics
    |--------------------------------------------------------------------------
    | When true, the /api/auth/windows endpoint includes a "debug" block in its
    | JSON response showing the raw IIS server variables and the resolved
    | username. Enable TEMPORARILY on the server (WINDOWS_AUTH_DEBUG=true) to
    | diagnose auth issues, then turn it back off — it exposes account names.
    */
    'debug' => env('WINDOWS_AUTH_DEBUG', false),

    /*
    | Optional default NetBIOS/DNS domain to prepend when LDAP lookups need a
    | qualified name. Leave null if not required.
    */
    'default_domain' => env('WINDOWS_AUTH_DEFAULT_DOMAIN'),
];
