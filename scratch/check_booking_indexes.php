<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    echo "Database: " . DB::connection()->getDatabaseName() . "\n";
    $tables = DB::select("SHOW TABLES LIKE '%booking%'");
    print_r($tables);
    $res = DB::select("SHOW CREATE TABLE bookings");
    print_r($res);
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
