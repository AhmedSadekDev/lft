<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    DB::statement("ALTER TABLE `bookings` ADD INDEX `idx_bookings_booking_number` (`booking_number`), ALGORITHM=INPLACE, LOCK=NONE");
    echo "Index added successfully.\n";

    $after = DB::select("EXPLAIN SELECT * FROM bookings WHERE booking_number = 'BK-100'");
    echo "EXPLAIN After:\n";
    print_r($after);
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
