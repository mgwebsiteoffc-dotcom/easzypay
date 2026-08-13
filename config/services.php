<?php

return [

    'stripe' => [
        'key'            => env('STRIPE_KEY'),
        'secret'         => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],

    'shopify' => [
        'app_key'        => env('SHOPIFY_APP_KEY'),
        'app_secret'     => env('SHOPIFY_APP_SECRET'),
        'store_domain'   => env('SHOPIFY_STORE_DOMAIN'),
        'access_token'   => env('SHOPIFY_ACCESS_TOKEN'),
        'api_version'    => env('SHOPIFY_API_VERSION', '2026-01'),
    ],

    'mailgun' => [
        'domain'   => env('MAILGUN_DOMAIN'),
        'secret'   => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme'   => 'https',
    ],

    'ses'     => ['key' => env('AWS_ACCESS_KEY_ID'), 'secret' => env('AWS_SECRET_ACCESS_KEY'), 'region' => env('AWS_DEFAULT_REGION', 'us-east-1')],
    'resend'  => ['key' => env('RESEND_KEY')],
    'slack'   => ['notifications' => ['bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'), 'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL')]],

];