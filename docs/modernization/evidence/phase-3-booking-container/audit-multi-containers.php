<?php
require __DIR__.'/phase3-bootstrap.php';

use App\Models\Booking;
use App\Models\BookingContainer;
use Illuminate\Support\Facades\DB;

echo "=== 1. MULTI-CONTAINER BOOKINGS AUDIT ===\n";
// Find bookings with count of containers > 1
$multiContainerBookings = Booking::has('bookingContainers', '>', 1)
    ->withCount('bookingContainers')
    ->get();

echo "Total bookings with >1 container: " . $multiContainerBookings->count() . "\n";
foreach ($multiContainerBookings->take(10) as $b) {
    echo "  Booking ID: {$b->id} ({$b->booking_number}) -> {$b->booking_containers_count} containers\n";
}

echo "\n=== 2. ACTIVE MULTI-CONTAINER BOOKINGS IN MISSIONS ===\n";
// Active containers matching any of the 4 stages, grouped by booking
$activeContainers = BookingContainer::withoutInvoicedBooking()
    ->where(function ($q) {
        $q->where(function ($sub) {
            $sub->where('status', 0)->orWhere(function ($s2) {
                $s2->where('status', 1)->where('superagent_specification_approved', 0);
            });
        })->orWhere(function ($sub) {
            $sub->where('superagent_specification_approved', 1)
                ->where('is_in_loading', 0)
                ->where('superagent_loading_approved', 0)
                ->where('superagent_unloading_approved', 0);
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
    ->select('id', 'booking_id', 'status', 'superagent_specification_approved', 'is_in_loading', 'superagent_loading_approved', 'superagent_unloading_approved')
    ->get();

$groupedByBooking = $activeContainers->groupBy('booking_id');
$activeMulti = $groupedByBooking->filter(fn($c) => $c->count() > 1);

echo "Total active bookings in missions: " . $groupedByBooking->count() . "\n";
echo "Active bookings with >1 active container in missions: " . $activeMulti->count() . "\n";
foreach ($activeMulti as $bookingId => $containers) {
    echo "  Booking ID {$bookingId} has " . $containers->count() . " active containers: [" . $containers->pluck('id')->implode(', ') . "]\n";
}

echo "\n=== 3. BOOKINGS WITH CONTAINERS ACROSS DIFFERENT STAGES ===\n";
function getContainerStage($c) {
    if ($c->superagent_specification_approved == 1 && $c->superagent_loading_approved == 1 && $c->superagent_unloading_approved == 0) return 'unloading';
    if ($c->superagent_specification_approved == 1 && $c->is_in_loading == 1 && $c->superagent_loading_approved == 0 && $c->superagent_unloading_approved == 0) return 'loading';
    if ($c->superagent_specification_approved == 1 && $c->is_in_loading == 0 && $c->superagent_loading_approved == 0 && $c->superagent_unloading_approved == 0) return 'waiting';
    if ($c->status == 0 || ($c->status == 1 && $c->superagent_specification_approved == 0)) return 'specification';
    return 'unknown';
}

$mixedStageBookings = [];
foreach ($activeMulti as $bookingId => $containers) {
    $stages = $containers->map(fn($c) => getContainerStage($c))->unique();
    if ($stages->count() > 1) {
        $mixedStageBookings[$bookingId] = $stages->values()->all();
        echo "  Booking ID {$bookingId} has MIXED STAGES: " . implode(', ', $stages->all()) . "\n";
        foreach ($containers as $c) {
            echo "    Container {$c->id} -> stage: " . getContainerStage($c) . "\n";
        }
    }
}
if (empty($mixedStageBookings)) {
    echo "  No bookings currently have containers in different stages simultaneously in active missions.\n";
}

echo "\n=== 4. MULTIPLE AGENTS ASSIGNMENTS AUDIT ===\n";
$multiAgentContainers = DB::table('booking_container_agents')
    ->select('booking_container_id', DB::raw('count(agent_id) as agent_count'))
    ->groupBy('booking_container_id')
    ->having('agent_count', '>', 1)
    ->get();

echo "Containers with >1 assigned agent: " . $multiAgentContainers->count() . "\n";
foreach ($multiAgentContainers->take(5) as $mac) {
    echo "  Container ID {$mac->booking_container_id} has {$mac->agent_count} agents\n";
}
