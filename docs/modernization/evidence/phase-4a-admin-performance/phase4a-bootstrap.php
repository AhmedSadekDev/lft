<?php
// Verification bootstrap for Phase 4A. Server-enforced READ ONLY session.
error_reporting(E_ALL);
ini_set('display_errors', '1');

require __DIR__.'/../../../../vendor/autoload.php';
$app = require_once __DIR__.'/../../../../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$db = \Illuminate\Support\Facades\DB::connection();

// Verify connection
$databaseName = $db->getDatabaseName();
if ($databaseName !== 'leader') {
    die("FATAL: Connected to unexpected database: {$databaseName}\n");
}

// Enforce read-only transaction/session for safety
$db->statement("SET SESSION TRANSACTION READ ONLY");

return ['app' => $app, 'db' => $db];
