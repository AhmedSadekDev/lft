<?php
require __DIR__.'/phase3-bootstrap.php';

use App\Models\Booking;
use App\Models\BookingContainer;

// 1. LEGACY IMPLEMENTATION LOGIC (extracted directly from BookingContainerController@all lines 37-124)
function runLegacyLogic($stageType = null, $page = 1, $perPage = 100)
{
    // Same stage queries as current lines 37-93
    $specItems = collect();
    if (!$stageType || $stageType === 'specification') {
        $specItems = BookingContainer::withoutInvoicedBooking()
            ->select('*')
            ->selectRaw("'specification' as stage_type")
            ->where(function ($q) {
                $q->where('status', 0)
                  ->orWhere(function($q2) {
                      $q2->where('status', 1)
                         ->where('superagent_specification_approved', 0);
                  });
            })
            ->get();
    }

    $waitingItems = collect();
    if (!$stageType || $stageType === 'waiting') {
        $waitingItems = BookingContainer::withoutInvoicedBooking()
            ->select('*')
            ->selectRaw("'waiting' as stage_type")
            ->where('superagent_specification_approved', 1)
            ->where('is_in_loading', 0)
            ->where('superagent_loading_approved', 0)
            ->where('superagent_unloading_approved', 0)
            ->get();
    }

    $loadingItems = collect();
    if (!$stageType || $stageType === 'loading') {
        $loadingItems = BookingContainer::withoutInvoicedBooking()
            ->select('*')
            ->selectRaw("'loading' as stage_type")
            ->where('superagent_specification_approved', 1)
            ->where('is_in_loading', 1)
            ->where('superagent_loading_approved', 0)
            ->where('superagent_unloading_approved', 0)
            ->get();
    }

    $unloadingItems = collect();
    if (!$stageType || $stageType === 'unloading') {
        $unloadingItems = BookingContainer::withoutInvoicedBooking()
            ->select('*')
            ->selectRaw("'unloading' as stage_type")
            ->where('superagent_specification_approved', 1)
            ->where('superagent_loading_approved', 1)
            ->where('superagent_unloading_approved', 0)
            ->get();
    }

    // Merge logic: lines 97-112
    $merged = $unloadingItems
        ->merge($loadingItems)
        ->merge($waitingItems)
        ->merge($specItems)
        ->unique('id')
        ->filter(fn ($container) => $container->booking_id !== null && Booking::where('id', $container->booking_id)->exists())
        ->sortByDesc('id')
        ->groupBy('booking_id')
        ->map(function ($containers) {
            $booking = Booking::find($containers->first()->booking_id);
            return [
                'booking_id' => $booking->id,
                'booking_number' => $booking->booking_number,
                'container_ids' => $containers->pluck('id')->all(),
                'container_stages' => $containers->pluck('stage_type', 'id')->all(),
            ];
        })
        ->sortByDesc('booking_id')
        ->values();

    $total = $merged->count();
    $results = $merged->forPage($page, $perPage)->values();

    return [
        'total' => $total,
        'per_page' => $perPage,
        'current_page' => $page,
        'bookings' => $results->all(),
    ];
}

// 2. CANDIDATE SQL-FIRST LOGIC
function stageConditionSql($stageType = null)
{
    return match ($stageType) {
        'specification' => function ($q) {
            $q->where(function ($sub) {
                $sub->where('status', 0)
                    ->orWhere(function ($sub2) {
                        $sub2->where('status', 1)
                             ->where('superagent_specification_approved', 0);
                    });
            });
        },
        'waiting' => function ($q) {
            $q->where('superagent_specification_approved', 1)
              ->where('is_in_loading', 0)
              ->where('superagent_loading_approved', 0)
              ->where('superagent_unloading_approved', 0);
        },
        'loading' => function ($q) {
            $q->where('superagent_specification_approved', 1)
              ->where('is_in_loading', 1)
              ->where('superagent_loading_approved', 0)
              ->where('superagent_unloading_approved', 0);
        },
        'unloading' => function ($q) {
            $q->where('superagent_specification_approved', 1)
              ->where('superagent_loading_approved', 1)
              ->where('superagent_unloading_approved', 0);
        },
        default => function ($q) {
            // Any of the 4 stages
            $q->where(function ($orQ) {
                // specification
                $orQ->where(function ($sub) {
                    $sub->where('status', 0)
                        ->orWhere(function ($sub2) {
                            $sub2->where('status', 1)
                                 ->where('superagent_specification_approved', 0);
                        });
                })
                // waiting
                ->orWhere(function ($sub) {
                    $sub->where('superagent_specification_approved', 1)
                        ->where('is_in_loading', 0)
                        ->where('superagent_loading_approved', 0)
                        ->where('superagent_unloading_approved', 0);
                })
                // loading
                ->orWhere(function ($sub) {
                    $sub->where('superagent_specification_approved', 1)
                        ->where('is_in_loading', 1)
                        ->where('superagent_loading_approved', 0)
                        ->where('superagent_unloading_approved', 0);
                })
                // unloading
                ->orWhere(function ($sub) {
                    $sub->where('superagent_specification_approved', 1)
                        ->where('superagent_loading_approved', 1)
                        ->where('superagent_unloading_approved', 0);
                });
            });
        }
    };
}

function runCandidateSqlLogic($stageType = null, $page = 1, $perPage = 100)
{
    $condition = stageConditionSql($stageType);

    // SQL-first query on Booking:
    // Only bookings that:
    // 1) Have NO invoice (withoutInvoice)
    // 2) Have at least one container matching the stage condition
    $query = Booking::query()
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
            }
        ])
        ->orderByDesc('id');

    $paginator = $query->paginate($perPage, ['*'], 'page', $page);

    $bookings = [];
    foreach ($paginator->items() as $booking) {
        $bookings[] = [
            'booking_id' => $booking->id,
            'booking_number' => $booking->booking_number,
            'container_ids' => $booking->bookingContainers->pluck('id')->all(),
            'container_stages' => $booking->bookingContainers->pluck('stage_type', 'id')->all(),
        ];
    }

    return [
        'total' => $paginator->total(),
        'per_page' => $paginator->perPage(),
        'current_page' => $paginator->currentPage(),
        'bookings' => $bookings,
    ];
}

// Compare across multiple scenarios
$testScenarios = [
    ['stage' => null, 'page' => 1, 'perPage' => 10],
    ['stage' => null, 'page' => 2, 'perPage' => 10],
    ['stage' => null, 'page' => 1, 'perPage' => 100],
    ['stage' => 'specification', 'page' => 1, 'perPage' => 10],
    ['stage' => 'waiting', 'page' => 1, 'perPage' => 10],
    ['stage' => 'loading', 'page' => 1, 'perPage' => 10],
    ['stage' => 'unloading', 'page' => 1, 'perPage' => 10],
    ['stage' => null, 'page' => 999, 'perPage' => 10],
];

$allMatch = true;
$comparisonResults = [];

foreach ($testScenarios as $sc) {
    $desc = "stage=" . ($sc['stage'] ?? 'all') . " page={$sc['page']} perPage={$sc['perPage']}";
    $legacy = runLegacyLogic($sc['stage'], $sc['page'], $sc['perPage']);
    $sql = runCandidateSqlLogic($sc['stage'], $sc['page'], $sc['perPage']);

    $match = ($legacy == $sql);
    if (!$match) $allMatch = false;

    $comparisonResults[$desc] = [
        'equal' => $match,
        'legacy_total' => $legacy['total'],
        'sql_total' => $sql['total'],
        'legacy_count' => count($legacy['bookings']),
        'sql_count' => count($sql['bookings']),
        'legacy_booking_ids' => array_column($legacy['bookings'], 'booking_id'),
        'sql_booking_ids' => array_column($sql['bookings'], 'booking_id'),
    ];
}

echo "OVERALL EQUIVALENCE: " . ($allMatch ? "100% IDENTICAL" : "MISMATCH DETECTED") . "\n";
echo json_encode($comparisonResults, JSON_PRETTY_PRINT) . "\n";
