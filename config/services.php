<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

    'paymongo' => [
        'public_key' => env('PAYMONGO_PUBLIC_KEY'),
        'secret_key' => env('PAYMONGO_SECRET_KEY'),
        'webhook_secret' => env('PAYMONGO_WEBHOOK_SECRET'),
        'live_mode' => (bool) env('PAYMONGO_LIVE_MODE', false),
        'payment_methods' => array_values(array_filter(array_map(
            static fn (string $method): string => trim($method),
            explode(',', (string) env('PAYMONGO_PAYMENT_METHODS', 'gcash,card,qrph')),
        ))),
        'webhook_tolerance' => (int) env('PAYMONGO_WEBHOOK_TOLERANCE', 300),
    ],

    'two_factor' => [
        'enabled' => (bool) env('TWO_FACTOR_ENABLED', true),
        'roles' => array_values(array_filter(array_map(
            static fn (string $role): string => trim($role),
            explode(',', (string) env('TWO_FACTOR_ROLES', 'admin')),
        ))),
        'ttl' => (int) env('TWO_FACTOR_TTL', 10),
        'max_attempts' => (int) env('TWO_FACTOR_MAX_ATTEMPTS', 5),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
