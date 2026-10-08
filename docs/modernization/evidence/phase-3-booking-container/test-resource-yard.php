<?php
require __DIR__.'/phase3-bootstrap.php';

use App\Models\Booking;
use App\Http\Resources\Api\Superagent\MissionBookingResource;

$bookingWithYard = Booking::whereNotNull('yard_id')
    ->whereHas('bookingContainers')
    ->first();

if (!$bookingWithYard) {
    echo "No booking with yard_id and bookingContainers found.\n";
    exit;
}

echo "Testing booking id {$bookingWithYard->id} with yard_id={$bookingWithYard->yard_id}:\n";
$bookingWithYard->load('bookingContainers');

try {
    $res = (new MissionBookingResource($bookingWithYard))->resolve();
    echo "SUCCESS: resolved resource successfully\n";
    echo "Containers count: " . count($res['booking_containers']) . "\n";
    if (count($res['booking_containers']) > 0) {
        $c = $res['booking_containers'][0];
        echo "Container yard_title: " . json_encode($c['yard_title']) . "\n";
        echo "Container yard_id: " . json_encode($c['yard_id']) . "\n";
    }
} catch (\Throwable $e) {
    echo "CAUGHT EXCEPTION: " . $e->getMessage() . "\n";
}
