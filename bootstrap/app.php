<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {

        // CORS for API and Shopify cart
        $middleware->prepend(\App\Http\Middleware\HandleCors::class);

        // Exclude CSRF for these paths
        $middleware->validateCsrfTokens(except: [
            'api/*',
            'webhook/*',
            'shopify/cart',
            'shopify/callback',
        ]);

        // Register named middleware
        $middleware->alias([
            'auth.admin'  => \App\Http\Middleware\AdminAuth::class,
            'auth.tenant' => \App\Http\Middleware\TenantAuth::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->create();