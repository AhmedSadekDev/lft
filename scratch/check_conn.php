<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "Default DB before config: " . config('database.default') . "\n";
echo "Schema connection before config: " . Schema::getConnection()->getName() . "\n";

config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
DB::disconnect('sqlite');
DB::reconnect('sqlite');

echo "Default DB after config: " . config('database.default') . "\n";
echo "Schema connection after config: " . Schema::getConnection()->getName() . "\n";
echo "Schema database name: " . Schema::getConnection()->getDatabaseName() . "\n";
