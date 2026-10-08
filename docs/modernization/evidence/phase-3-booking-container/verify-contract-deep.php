<?php
require __DIR__.'/phase3-bootstrap.php';

use App\Models\Booking;
use App\Models\BookingContainer;
use Illuminate\Pagination\LengthAwarePaginator;

echo "========================================================================\n";
echo "EXHAUSTIVE CONTRACT VERIFICATION: LEGACY VS SQL-OPTIMIZED\n";
echo "========================================================================\n\n";

// 1. REPRODUCE LEGACY ENGINE EXACTLY (as it was in BookingContainerController@all)
function runLegacyExact($stageType = null, $perPage = 100, $page = 1)
{
    $with = [
        'booking.company',
        'booking.factory',
        'branch.factory',
        'container',
        'notes',
        'agents',
    ];

    $specItems = collect();
    if (!$stageType || $stageType === 'specification') {
        $specItems = BookingContainer::with($with)
            ->withoutInvoicedBooking()
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
        $waitingItems = BookingContainer::with($with)
            ->withoutInvoicedBooking()
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
        $loadingItems = BookingContainer::with($with)
            ->withoutInvoicedBooking()
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
        $unloadingItems = BookingContainer::with($with)
            ->withoutInvoicedBooking()
            ->select('*')
            ->selectRaw("'unloading' as stage_type")
            ->where('superagent_specification_approved', 1)
            ->where('superagent_loading_approved', 1)
            ->where('superagent_unloading_approved', 0)
            ->get();
    }

    $merged = $unloadingItems
        ->merge($loadingItems)
        ->merge($waitingItems)
        ->merge($specItems)
        ->unique('id')
        ->filter(fn ($container) => $container->booking !== null)
        ->sortByDesc('id')
        ->groupBy('booking_id')
        ->map(function ($containers) {
            $booking = $containers->first()->booking;
            $booking->setRelation('bookingContainers', $containers->values());
            return $booking;
        })
        ->sortByDesc('id')
        ->values();

    $total     = $merged->count();
    $results   = $merged->forPage($page, $perPage)->values();
    $paginator = new LengthAwarePaginator($results, $total, $perPage, $page);

    return $paginator;
}

// 2. SQL-OPTIMIZED ENGINE (WITHOUT ARTIFICIAL CAP ON per_page)
function runSqlExact($stageType = null, $perPage = 100, $page = 1)
{
    $condition = match ($stageType) {
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
            $q->where(function ($orQ) {
                $orQ->where(function ($sub) {
                    $sub->where('status', 0)
                        ->orWhere(function ($sub2) {
                            $sub2->where('status', 1)
                                 ->where('superagent_specification_approved', 0);
                        });
                })
                ->orWhere(function ($sub) {
                    $sub->where('superagent_specification_approved', 1)
                        ->where('is_in_loading', 0)
                        ->where('superagent_loading_approved', 0)
                        ->where('superagent_unloading_approved', 0);
                })
                ->orWhere(function ($sub) {
                    $sub->where('superagent_specification_approved', 1)
                        ->where('is_in_loading', 1)
                        ->where('superagent_loading_approved', 0)
                        ->where('superagent_unloading_approved', 0);
                })
                ->orWhere(function ($sub) {
                    $sub->where('superagent_specification_approved', 1)
                        ->where('superagent_loading_approved', 1)
                        ->where('superagent_unloading_approved', 0);
                });
            });
        },
    };

    $query = Booking::query()
        ->withoutInvoice()
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
                    ->with([
                        'booking.company',
                        'booking.factory',
                        'branch.factory',
                        'container',
                        'notes',
                        'agents',
                    ])
                    ->orderByDesc('id');
            }
        ])
        ->orderByDesc('id');

    return $query->paginate($perPage, ['*'], 'page', $page);
}

// ========================================================================
// TEST 1: MULTI-CONTAINER BOOKINGS DETAILED COMPARISON
// ========================================================================
echo "TEST 1: MULTI-CONTAINER BOOKINGS INTEGRITY\n";
echo "------------------------------------------------------------------------\n";
$legacyAll = runLegacyExact(null, 100, 1);
$sqlAll = runSqlExact(null, 100, 1);

$multiContainerBookingIds = [399, 400, 413, 425, 440, 471, 476, 477, 478, 479, 499, 507, 508];
$allMultiMatch = true;

foreach ($multiContainerBookingIds as $bId) {
    $legacyBooking = collect($legacyAll->items())->firstWhere('id', $bId);
    $sqlBooking = collect($sqlAll->items())->firstWhere('id', $bId);

    if (!$legacyBooking || !$sqlBooking) {
        echo "  [FAIL] Booking $bId not found in one of the sets!\n";
        $allMultiMatch = false;
        continue;
    }

    $legacyContainers = $legacyBooking->bookingContainers->map(fn($c) => [
        'id' => $c->id,
        'stage_type' => $c->stage_type,
        'agents_count' => $c->agents->count(),
    ])->values()->all();

    $sqlContainers = $sqlBooking->bookingContainers->map(fn($c) => [
        'id' => $c->id,
        'stage_type' => $c->stage_type,
        'agents_count' => $c->agents->count(),
    ])->values()->all();

    $match = ($legacyContainers === $sqlContainers);
    if (!$match) $allMultiMatch = false;

    $cCount = count($sqlContainers);
    $status = $match ? "PASS (100% IDENTICAL)" : "FAIL (MISMATCH)";
    echo "  Booking $bId ($cCount containers): $status\n";
    if (!$match) {
        echo "    Legacy: " . json_encode($legacyContainers) . "\n";
        echo "    SQL:    " . json_encode($sqlContainers) . "\n";
    }
}
echo "Result TEST 1: " . ($allMultiMatch ? "ALL 13 MULTI-CONTAINER BOOKINGS MATCH 100%\n\n" : "FAILED!\n\n");

// ========================================================================
// TEST 2: PER_PAGE > 250 TEST (e.g. 300, 500, 1000)
// ========================================================================
echo "TEST 2: PER_PAGE VALUES GREATER THAN 250\n";
echo "------------------------------------------------------------------------\n";
$perPageValues = [250, 300, 500, 1000];
$allPerPageMatch = true;

foreach ($perPageValues as $pp) {
    $leg = runLegacyExact(null, $pp, 1);
    $sql = runSqlExact(null, $pp, 1);

    $eqTotal = ($leg->total() === $sql->total());
    $eqPerPage = ($leg->perPage() === $sql->perPage());
    $eqCount = ($leg->count() === $sql->count());
    $eqIds = (collect($leg->items())->pluck('id')->all() === collect($sql->items())->pluck('id')->all());

    $pass = $eqTotal && $eqPerPage && $eqCount && $eqIds;
    if (!$pass) $allPerPageMatch = false;

    echo "  per_page = $pp: " . ($pass ? "PASS" : "FAIL") . " (Total: {$sql->total()}, Items returned: {$sql->count()}, PerPage in Meta: {$sql->perPage()})\n";
}
echo "Result TEST 2: " . ($allPerPageMatch ? "PER_PAGE > 250 CONTRACT 100% PRESERVED WITHOUT CAP\n\n" : "FAILED!\n\n");

// ========================================================================
// TEST 3: CROSS-STAGE SIMULATION AND ISOLATION
// ========================================================================
echo "TEST 3: STAGE FILTERING & CONTAINER ISOLATION\n";
echo "------------------------------------------------------------------------\n";
// Test each stage filter and verify that only containers matching that stage are loaded in the booking
$stages = ['specification', 'waiting', 'loading', 'unloading'];
$allStagesMatch = true;

foreach ($stages as $st) {
    $leg = runLegacyExact($st, 100, 1);
    $sql = runSqlExact($st, 100, 1);

    $eqTotal = ($leg->total() === $sql->total());
    $eqIds = (collect($leg->items())->pluck('id')->all() === collect($sql->items())->pluck('id')->all());
    
    // Check that every loaded container in SQL actually belongs to $st
    $allContainersInStage = true;
    foreach ($sql->items() as $b) {
        foreach ($b->bookingContainers as $c) {
            if ($c->stage_type !== $st) {
                $allContainersInStage = false;
            }
        }
    }

    $pass = $eqTotal && $eqIds && $allContainersInStage;
    if (!$pass) $allStagesMatch = false;

    echo "  stage_type = '$st': " . ($pass ? "PASS" : "FAIL") . " (Bookings: {$sql->total()}, All containers have stage_type='$st': " . ($allContainersInStage ? "YES" : "NO") . ")\n";
}
echo "Result TEST 3: " . ($allStagesMatch ? "STAGE ISOLATION & FILTERING 100% ACCURATE\n\n" : "FAILED!\n\n");

// ========================================================================
// SUMMARY CONCLUSION
// ========================================================================
$overallSuccess = $allMultiMatch && $allPerPageMatch && $allStagesMatch;
echo "========================================================================\n";
echo "OVERALL VERIFICATION: " . ($overallSuccess ? "ALL CONTRACT TESTS PASSED WITH 100% EQUIVALENCE" : "TESTS FAILED") . "\n";
echo "========================================================================\n";
