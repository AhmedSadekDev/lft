<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
DB::reconnect('sqlite');

Schema::create('test_tbl', function (Blueprint $t) {
    $t->id();
});
DB::table('test_tbl')->insert(['id' => 1]);
echo "Rows before: " . DB::table('test_tbl')->count() . "\n";

Schema::dropAllTables();
echo "Tables after dropAllTables: " . count(Schema::getAllTables()) . "\n";
