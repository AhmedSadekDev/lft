<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Agent;
use App\Models\Superagent;
use App\Models\Company;
use App\Models\Booking;
use App\Models\BookingContainer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
DB::purge('sqlite');
DB::reconnect('sqlite');
Schema::dropAllTables();

Schema::create('agents', function (Blueprint $t) {
    $t->increments('id');
    $t->string('name')->default('Test Agent');
    $t->string('email')->nullable();
    $t->string('phone')->nullable();
    $t->decimal('wallet', 15, 2)->default(0);
    $t->decimal('total_wallet', 15, 2)->default(0);
    $t->decimal('spented_financial_custody', 15, 2)->default(0);
    $t->timestamps();
});
Schema::create('superagents', function (Blueprint $t) {
    $t->increments('id');
    $t->string('name')->default('Test Superagent');
    $t->timestamps();
});
Schema::create('app_notifications', function (Blueprint $t) {
    $t->increments('id');
    $t->string('title')->nullable();
    $t->text('text')->nullable();
    $t->unsignedInteger('notificationable_id')->nullable();
    $t->string('notificationable_type')->nullable();
    $t->unsignedInteger('booking_container_id')->nullable();
    $t->integer('type')->default(0);
    $t->boolean('is_read')->default(false);
    $t->timestamps();
});
Schema::create('companies', function (Blueprint $t) {
    $t->increments('id');
    $t->string('name')->default('Test Company');
    $t->timestamps();
});
Schema::create('bookings', function (Blueprint $t) {
    $t->increments('id');
    $t->string('booking_number')->default('BK-001');
    $t->unsignedInteger('company_id')->nullable();
    $t->timestamps();
});
Schema::create('booking_containers', function (Blueprint $t) {
    $t->increments('id');
    $t->unsignedInteger('booking_id');
    $t->string('container_no')->default('CONT-001');
    $t->timestamps();
});
Schema::create('invoices', function (Blueprint $t) {
    $t->increments('id');
    $t->unsignedInteger('booking_id');
    $t->timestamps();
});

$agent = Agent::create(['name' => 'Agent Ali']);
auth()->guard('agent')->setUser($agent);
$req = \Illuminate\Http\Request::create('/api/agent/fetch_your_notifications', 'POST', ['per_page' => 2]);
$notifController = app(\App\Http\Controllers\Api\Agent\NotificationController::class);
$res1 = $notifController->fetch_notifications($req);

print_r($res1->getData(true));
