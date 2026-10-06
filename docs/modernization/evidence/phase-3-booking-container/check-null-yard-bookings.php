<?php
require __DIR__.'/phase3-bootstrap.php';

use App\Models\Booking;

$bookingsNullYard = Booking::whereNull('yard_id')
    ->whereDoesntHave('invoice')
    ->whereHas('bookingContainers', function($q) {
        $q->where(function ($sub) {
            $sub->where('status', 0)
                ->orWhere(function ($sub2) {
                    $sub2->where('status', 1)->where('superagent_specification_approved', 0);
                });
        })->orWhere(function ($sub) {
            $sub->where('superagent_specification_approved', 1)
                ->where('is_in_loading', 1)
                ->where('superagent_loading_approved', 0)
                ->where('superagent_unloading_approved', 0);
        })->orWhere(function ($sub) {
            $sub->where('superagent_specification_approved', 1)
                ->where('superagent_loading_approved', 1)
                ->where('superagent_unloading_approved', 0);
        });
    })
    ->pluck('id')->all();

echo "Bookings with NULL yard_id and active containers: " . count($bookingsNullYard) . "\n";
echo "IDs: " . implode(', ', $bookingsNullYard) . "\n";
