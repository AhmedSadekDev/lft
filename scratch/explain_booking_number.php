<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$before = DB::select("EXPLAIN SELECT * FROM bookings WHERE booking_number = 'BK-100'");
echo "EXPLAIN Before:\n";
print_r($before);
