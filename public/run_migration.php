<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $filename = trim($_POST['migration']);

    $kernel->call('migrate', [
        '--path' => 'database/migrations/' . $filename,
        '--force' => true,
    ]);

    echo "<pre>".$kernel->output()."</pre>";
    exit;
}
?>

<form method="post">
    <label>Migration Filename:</label><br>
    <input type="text" name="migration" style="width:500px"
        placeholder="2026_07_07_123456_create_users_table.php">
    <br><br>
    <button type="submit">Run Migration</button>
</form>