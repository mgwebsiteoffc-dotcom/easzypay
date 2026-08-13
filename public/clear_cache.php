<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->call('cache:clear');
$kernel->call('config:clear');
$kernel->call('route:clear');
$kernel->call('view:clear');
$kernel->call('event:clear');
$kernel->call('optimize:clear');

echo "Laravel cache cleared successfully!";