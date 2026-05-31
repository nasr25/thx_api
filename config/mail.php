<?php

return [

    'default' => env('MAIL_MAILER', 'smtp'),

    'mailers' => [

        'smtp' => [
            'transport'    => 'smtp',
            'url'          => env('MAIL_URL'),
            'host'         => env('MAIL_HOST', '127.0.0.1'),
            'port'         => env('MAIL_PORT', 587),
            'encryption'   => env('MAIL_ENCRYPTION', 'tls'),
            'username'     => env('MAIL_USERNAME'),
            'password'     => env('MAIL_PASSWORD'),
            'timeout'      => null,
            'local_domain' => env('MAIL_EHLO_DOMAIN', parse_url((string) env('APP_URL', 'http://localhost'), PHP_URL_HOST)),

            // When false, the SMTP TLS certificate is NOT verified. Use this for
            // internal mail servers with self-signed / untrusted certificates.
            'verify_peer'  => filter_var(env('MAIL_VERIFY_PEER', true), FILTER_VALIDATE_BOOLEAN),
        ],

        'smtps' => [
            'transport'   => 'smtp',
            'host'        => env('MAIL_HOST', '127.0.0.1'),
            'port'        => env('MAIL_PORT', 465),
            'encryption'  => 'tls',
            'username'    => env('MAIL_USERNAME'),
            'password'    => env('MAIL_PASSWORD'),
            'verify_peer' => filter_var(env('MAIL_VERIFY_PEER', true), FILTER_VALIDATE_BOOLEAN),
        ],

        'sendmail' => [
            'transport' => 'sendmail',
            'path'      => env('MAIL_SENDMAIL_PATH', '/usr/sbin/sendmail -bs -i'),
        ],

        'log' => [
            'transport' => 'log',
            'channel'   => env('MAIL_LOG_CHANNEL'),
        ],

        'array' => [
            'transport' => 'array',
        ],

        'failover' => [
            'transport' => 'failover',
            'mailers'   => ['smtp', 'log'],
        ],
    ],

    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'noreply@company.com'),
        'name'    => env('MAIL_FROM_NAME', 'Appreciation Platform'),
    ],

];
