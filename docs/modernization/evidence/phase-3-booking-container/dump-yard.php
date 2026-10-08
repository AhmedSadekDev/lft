<?php
require __DIR__.'/phase3-bootstrap.php';
use App\Models\Booking;
use App\Http\Resources\Api\Superagent\allBookingContainerResource;

$b = Booking::find(2);
$c = $b->bookingContainers->first();

// Test setting relation 'yard' to null on $c->booking:
$c->booking->setRelation('yard', null);

try {
    $res = (new allBookingContainerResource($c))->resolve();
    echo "RESOLVED SUCCESSFULLY!\n";
    echo "yard_title: " . json_encode($res['yard_title']) . "\n";
    echo "yard_id: " . json_encode($res['yard_id']) . "\n";
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
