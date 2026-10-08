<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$tables = \Illuminate\Support\Facades\DB::select('SHOW TABLES');
$tblList = array_map(function($t) { return array_values((array)$t)[0]; }, $tables);
echo json_encode($tblList, JSON_PRETTY_PRINT);
