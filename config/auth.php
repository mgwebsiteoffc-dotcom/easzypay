<?php

return [

    'defaults' => [
        'guard'     => 'web',
        'passwords' => 'users',
    ],

    'guards' => [
        'web' => [
            'driver'   => 'session',
            'provider' => 'users',
        ],

        // Admin guard
        'admin' => [
            'driver'   => 'session',
            'provider' => 'admin_users',
        ],

        // Tenant/Merchant guard
        'tenant' => [
            'driver'   => 'session',
            'provider' => 'tenants',
        ],
    ],

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model'  => env('AUTH_MODEL', App\Models\User::class),
        ],

        'admin_users' => [
            'driver' => 'eloquent',
            'model'  => App\Models\AdminUser::class,
        ],

        'tenants' => [
            'driver' => 'eloquent',
            'model'  => App\Models\Tenant::class,
        ],
    ],

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table'    => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
            'expire'   => 60,
            'throttle' => 60,
        ],

        'tenants' => [
            'provider' => 'tenants',
            'table'    => 'tenant_password_reset_tokens',
            'expire'   => 60,
            'throttle' => 60,
        ],
    ],

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),

];