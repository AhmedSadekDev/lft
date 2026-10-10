<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\BookingContainer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
DB::reconnect('sqlite');

Schema::create('booking_containers', function (Blueprint $t) {
    $t->increments('id');
    $t->unsignedInteger('booking_id');
    $t->string('container_no')->default('CONT-001');
    $t->integer('status')->default(0);
    $t->boolean('superagent_specification_approved')->default(0);
    $t->boolean('superagent_loading_approved')->default(0);
    $t->boolean('superagent_unloading_approved')->default(0);
    $t->boolean('is_in_loading')->default(0);
    $t->timestamps();
});

DB::enableQueryLog();

$c = BookingContainer::create([
    'booking_id' => 1,
    'container_no' => 'CONT-REC-1',
    'status' => 1,
    'superagent_specification_approved' => 1,
    'is_in_loading' => 1,
]);

print_r(DB::getQueryLog());
echo "Row in DB:\n";
print_r(DB::table('booking_containers')->first());
