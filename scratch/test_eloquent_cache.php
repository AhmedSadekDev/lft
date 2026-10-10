<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\BookingContainer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);

// 1. Create partial table
Schema::create('booking_containers', function (Blueprint $t) {
    $t->id();
    $t->string('container_no');
});
$c1 = BookingContainer::create(['container_no' => 'OLD', 'status' => 1]);
echo "C1 status: " . ($c1->status ?? 'NULL') . "\n";

// 2. Drop and recreate with extra column
Schema::dropAllTables();
DB::disconnect('sqlite');
DB::reconnect('sqlite');

Schema::create('booking_containers', function (Blueprint $t) {
    $t->id();
    $t->string('container_no');
    $t->integer('status')->default(0);
});

$c2 = BookingContainer::create(['container_no' => 'NEW', 'status' => 1]);
echo "C2 status: " . ($c2->status ?? 'NULL') . "\n";
echo "C2 in DB status: " . DB::table('booking_containers')->where('id', $c2->id)->value('status') . "\n";
