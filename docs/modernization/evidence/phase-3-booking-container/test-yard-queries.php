<?php
require __DIR__.'/phase3-bootstrap.php';

use App\Models\Booking;
use App\Http\Resources\Api\Superagent\MissionBookingResource;

$queries = [];
$db->listen(function ($q) use (&$queries) {
    $queries[] = $q->sql;
});

$booking = Booking::whereNotNull('yard_id')->whereHas('bookingContainers')->first();
$booking->load(['bookingContainers' => function ($q) {
    $q->with(['booking.company', 'booking.factory', 'branch.factory', 'container', 'notes', 'agents']);
}]);

echo "Booking ID: {$booking->id}, yard_id: {$booking->yard_id}\n";
try {
    $res = (new MissionBookingResource($booking))->resolve();
    echo "Resolved successfully!\n";
} catch (\Throwable $e) {
    echo "Caught: " . $e->getMessage() . "\n";
}

echo "Queries run:\n" . implode("\n", $queries) . "\n";
