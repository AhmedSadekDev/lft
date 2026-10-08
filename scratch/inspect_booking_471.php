<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$booking = \App\Models\Booking::find(471) ?? \App\Models\Booking::first();
if ($booking) {
    echo "Booking ID: " . $booking->id . ", Number: " . $booking->booking_number . ", Containers: " . $booking->bookingContainers()->count() . "\n";
} else {
    echo "No booking found!\n";
}
