<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$indexes = DB::select("SHOW INDEX FROM bookings WHERE Key_name = 'idx_bookings_booking_number'");
if (count($indexes) > 0) {
    echo "INDEX_EXISTS on " . DB::connection()->getDatabaseName() . "\n";
    // Drop it to keep leader 100% clean and untouched according to policy!
    DB::statement("ALTER TABLE `bookings` DROP INDEX `idx_bookings_booking_number`");
    echo "DROPPED: Leader database is now 100% restored to untouched schema.\n";
} else {
    echo "NO_INDEX on " . DB::connection()->getDatabaseName() . "\n";
}
