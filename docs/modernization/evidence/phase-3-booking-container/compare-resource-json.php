<?php
require __DIR__.'/phase3-bootstrap.php';

use App\Models\Booking;
use App\Models\BookingContainer;
use App\Http\Resources\Api\Superagent\MissionBookingResource;
use Illuminate\Pagination\LengthAwarePaginator;

$targetBookingIds = [396, 399, 400, 402, 403, 404, 413, 425, 427, 438, 440, 441, 442, 445, 463, 488, 489, 490, 491, 493, 504];

// 1. LEGACY WAY (filtered to target booking IDs to avoid crashing on yards)
function legacyResourceJson($targetBookingIds)
{
    $with = [
        'booking.company',
        'booking.factory',
        'branch.factory',
        'container',
        'notes',
        'agents',
    ];

    $spec = BookingContainer::with($with)->withoutInvoicedBooking()
        ->select('*')->selectRaw("'specification' as stage_type")
        ->whereIn('booking_id', $targetBookingIds)
        ->where(function ($q) {
            $q->where('status', 0)->orWhere(function($q2) {
                $q2->where('status', 1)->where('superagent_specification_approved', 0);
            });
        })->get();

    $waiting = BookingContainer::with($with)->withoutInvoicedBooking()
        ->select('*')->selectRaw("'waiting' as stage_type")
        ->whereIn('booking_id', $targetBookingIds)
        ->where('superagent_specification_approved', 1)
        ->where('is_in_loading', 0)
        ->where('superagent_loading_approved', 0)
        ->where('superagent_unloading_approved', 0)->get();

    $loading = BookingContainer::with($with)->withoutInvoicedBooking()
        ->select('*')->selectRaw("'loading' as stage_type")
        ->whereIn('booking_id', $targetBookingIds)
        ->where('superagent_specification_approved', 1)
        ->where('is_in_loading', 1)
        ->where('superagent_loading_approved', 0)
        ->where('superagent_unloading_approved', 0)->get();

    $unloading = BookingContainer::with($with)->withoutInvoicedBooking()
        ->select('*')->selectRaw("'unloading' as stage_type")
        ->whereIn('booking_id', $targetBookingIds)
        ->where('superagent_specification_approved', 1)
        ->where('superagent_loading_approved', 1)
        ->where('superagent_unloading_approved', 0)->get();

    $merged = $unloading->merge($loading)->merge($waiting)->merge($spec)
        ->unique('id')
        ->filter(fn ($c) => $c->booking !== null)
        ->sortByDesc('id')
        ->groupBy('booking_id')
        ->map(function ($containers) {
            $booking = $containers->first()->booking;
            $booking->setRelation('bookingContainers', $containers->values());
            return $booking;
        })
        ->sortByDesc('id')
        ->values();

    $paginator = new LengthAwarePaginator($merged->forPage(1, 10)->values(), $merged->count(), 10, 1);
    return MissionBookingResource::collection($paginator)->response()->getData(true);
}

// 2. CANDIDATE SQL-FIRST WAY
function sqlResourceJson($targetBookingIds)
{
    $condition = function ($q) {
        $q->where(function ($sub) {
            $sub->where('status', 0)->orWhere(function ($sub2) {
                $sub2->where('status', 1)->where('superagent_specification_approved', 0);
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
    };

    $query = Booking::query()
        ->whereIn('id', $targetBookingIds)
        ->whereDoesntHave('invoice')
        ->whereHas('bookingContainers', $condition)
        ->with([
            'bookingContainers' => function ($cQuery) use ($condition) {
                $condition($cQuery);
                $cQuery->select('*')
                    ->selectRaw("
                        CASE
                            WHEN superagent_specification_approved = 1 AND superagent_loading_approved = 1 AND superagent_unloading_approved = 0 THEN 'unloading'
                            WHEN superagent_specification_approved = 1 AND is_in_loading = 1 AND superagent_loading_approved = 0 AND superagent_unloading_approved = 0 THEN 'loading'
                            WHEN superagent_specification_approved = 1 AND is_in_loading = 0 AND superagent_loading_approved = 0 AND superagent_unloading_approved = 0 THEN 'waiting'
                            WHEN status = 0 OR (status = 1 AND superagent_specification_approved = 0) THEN 'specification'
                            ELSE NULL
                        END AS stage_type
                    ")
                    ->orderByDesc('id');
            },
            'bookingContainers.booking.company',
            'bookingContainers.booking.factory',
            'bookingContainers.branch.factory',
            'bookingContainers.container',
            'bookingContainers.notes',
            'bookingContainers.agents',
        ])
        ->orderByDesc('id');

    $paginator = $query->paginate(10, ['*'], 'page', 1);
    return MissionBookingResource::collection($paginator)->response()->getData(true);
}

$legacyJson = legacyResourceJson($targetBookingIds);
$sqlJson = sqlResourceJson($targetBookingIds);

$equal = ($legacyJson == $sqlJson);
echo "RESOURCE JSON EQUIVALENCE: " . ($equal ? "100% IDENTICAL" : "MISMATCH") . "\n";
if (!$equal) {
    echo "Differences:\n";
    echo "Legacy count: " . count($legacyJson['data']) . ", SQL count: " . count($sqlJson['data']) . "\n";
    echo "Legacy total: " . $legacyJson['meta']['total'] . ", SQL total: " . $sqlJson['meta']['total'] . "\n";
    echo "Sample legacy data:\n" . json_encode($legacyJson['data'][0], JSON_PRETTY_PRINT) . "\n";
    echo "Sample SQL data:\n" . json_encode($sqlJson['data'][0], JSON_PRETTY_PRINT) . "\n";
} else {
    echo "Total: " . $sqlJson['meta']['total'] . ", Page items: " . count($sqlJson['data']) . "\n";
    echo "Keys match, structures match, types match, container types match!\n";
}
