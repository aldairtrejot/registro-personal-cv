<?php

return [

    'default' => env('MAIL_MAILER', 'smtp'),

    'mailers' => [

        'smtp' => [
            'transport' => 'smtp',
            'scheme' => env('MAIL_ENCRYPTION') === 'ssl' ? 'smtps' : null,
            'host' => env('MAIL_HOST', '127.0.0.1'),
            'port' => (int) env('MAIL_PORT', 587),
            'username' => env('MAIL_USERNAME'),
            'password' => env('MAIL_PASSWORD'),
            'timeout' => (int) env('MAIL_TIMEOUT', 30),
            'local_domain' => env(
                'MAIL_EHLO_DOMAIN',
                parse_url(env('APP_URL', 'http://localhost'), PHP_URL_HOST)
            ),

            // Laravel 9+ / Symfony Mailer
            'verify_peer' => filter_var(env('MAIL_VERIFY_PEER', true), FILTER_VALIDATE_BOOL),
        ],

        'ses' => [
            'transport' => 'ses',
        ],

        'postmark' => [
            'transport' => 'postmark',
        ],

        'resend' => [
            'transport' => 'resend',
        ],

        'sendmail' => [
            'transport' => 'sendmail',
            'path' => env('MAIL_SENDMAIL_PATH', '/usr/sbin/sendmail -bs -i'),
        ],

        'log' => [
            'transport' => 'log',
            'channel' => env('MAIL_LOG_CHANNEL'),
        ],

        'array' => [
            'transport' => 'array',
        ],

        'failover' => [
            'transport' => 'failover',
            'mailers' => ['smtp', 'log'],
            'retry_after' => 60,
        ],

        'roundrobin' => [
            'transport' => 'roundrobin',
            'mailers' => ['smtp', 'log'],
            'retry_after' => 60,
        ],

    ],

    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
        'name' => env('MAIL_FROM_NAME', 'Example'),
    ],

];