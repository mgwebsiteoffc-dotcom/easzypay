<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Http\Kernel');

echo "<h2>View Debug</h2><pre>";

$views = [
    'tenant.auth.register',
    'tenant.auth.login',
    'tenant.layout',
    'tenant.dashboard',
    'shopify.invalid-shop',
    'shopify.error',
    'admin.auth.login',
    'marketing.home',
    'checkout.show',
    'checkout.success',
    'checkout.expired',
    'checkout.cancel',
];

foreach ($views as $view) {
    $exists = view()->exists($view);
    $status = $exists ? '✅' : '❌';
    echo "{$status} {$view}\n";

    if ($exists) {
        $path = view($view)->getPath();
        echo "   Path: {$path}\n";
        echo "   Size: " . filesize($path) . " bytes\n";
    }
}

echo "\n--- PHP Info ---\n";
echo "PHP: " . phpversion() . "\n";
echo "Memory: " . ini_get('memory_limit') . "\n";

echo "</pre>";