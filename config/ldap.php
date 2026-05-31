<?php

return [
    'default' => env('LDAP_CONNECTION', 'default'),

    'connections' => [
        'default' => [
            'hosts'            => [env('LDAP_HOST', 'ldap.company.com')],
            'username'         => env('LDAP_USERNAME', ''),
            'password'         => env('LDAP_PASSWORD', ''),
            'port'             => (int) env('LDAP_PORT', 389),
            'base_dn'          => env('LDAP_BASE_DN', 'dc=company,dc=com'),
            'timeout'          => (int) env('LDAP_TIMEOUT', 5),
            'use_ssl'          => (bool) env('LDAP_SSL', false),
            'use_tls'          => (bool) env('LDAP_TLS', false),
            'use_sasl'         => false,
            'version'          => 3,
            'follow_referrals' => false,
        ],
    ],

    'logging' => env('LDAP_LOGGING', false),

    'cache' => [
        'enabled'  => false,
        'driver'   => 'file',
        'lifetime' => 3600,
    ],
];
