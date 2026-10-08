<?php
require __DIR__.'/phase4a-bootstrap.php';

use App\Models\Booking;
use Illuminate\Http\Request;

echo "=== COMPREHENSIVE STAGE COUNTS EQUIVALENCE TEST ===\n\n";

$scenarios = [
    'Default (no filters)' => [],
    'With Company Filter' => ['company_id' => 5],
    'With Another Company' => ['company_id' => 6],
    'With Date Filter' => ['date_from' => '2026-08-01', 'date_to' => '2026-10-01'],
    'With Booking Number' => ['booking_number' => '500'],
    'With Search Term' => ['search' => 'test'],
];

$allPassed = true;

foreach ($scenarios as $name => $params) {
    $request = Request::create('/admin/bookings', 'GET', $params);

    // Legacy method
    $qLegacy = Booking::query()->filterListing($request);
    $legacyCounts = [
        'all'       => (clone $qLegacy)->count(),
        'assigned'  => (clone $qLegacy)->filterStage('assigned')->count(),
        'waiting'   => (clone $qLegacy)->filterStage('waiting')->count(),
        'loading'   => (clone $qLegacy)->filterStage('loading')->count(),
        'unloading' => (clone $qLegacy)->filterStage('unloading')->count(),
        'invoiced'  => (clone $qLegacy)->filterStage('invoiced')->count(),
    ];

    // Optimized method
    $qOpt = Booking::query()->filterListing($request);
    $stageRow = (clone $qOpt)
        ->setEagerLoads([])
        ->selectRaw("
            COUNT(*) as count_all,
            COUNT(CASE WHEN EXISTS (
                SELECT 1 FROM `booking_containers` 
                WHERE `booking_containers`.`booking_id` = `bookings`.`id` 
                  AND (`status` = 0 OR (`status` = 1 AND `superagent_specification_approved` = 0))
            ) THEN 1 END) as count_assigned,
            COUNT(CASE WHEN EXISTS (
                SELECT 1 FROM `booking_containers` 
                WHERE `booking_containers`.`booking_id` = `bookings`.`id` 
                  AND `superagent_specification_approved` = 1 
                  AND `is_in_loading` = 0 
                  AND `superagent_loading_approved` = 0
            ) THEN 1 END) as count_waiting,
            COUNT(CASE WHEN EXISTS (
                SELECT 1 FROM `booking_containers` 
                WHERE `booking_containers`.`booking_id` = `bookings`.`id` 
                  AND `superagent_specification_approved` = 1 
                  AND `is_in_loading` = 1 
                  AND `superagent_loading_approved` = 0
            ) THEN 1 END) as count_loading,
            COUNT(CASE WHEN EXISTS (
                SELECT 1 FROM `booking_containers` 
                WHERE `booking_containers`.`booking_id` = `bookings`.`id` 
                  AND `superagent_loading_approved` = 1 
                  AND `superagent_unloading_approved` = 0
            ) THEN 1 END) as count_unloading,
            COUNT(CASE WHEN EXISTS (
                SELECT 1 FROM `invoices` 
                WHERE `invoices`.`booking_id` = `bookings`.`id`
            ) OR EXISTS (
                SELECT 1 FROM `booking_containers` 
                WHERE `booking_containers`.`booking_id` = `bookings`.`id` 
                  AND `superagent_unloading_approved` = 1
            ) THEN 1 END) as count_invoiced
        ")
        ->first();

    $optCounts = [
        'all'       => (int) ($stageRow->count_all ?? 0),
        'assigned'  => (int) ($stageRow->count_assigned ?? 0),
        'waiting'   => (int) ($stageRow->count_waiting ?? 0),
        'loading'   => (int) ($stageRow->count_loading ?? 0),
        'unloading' => (int) ($stageRow->count_unloading ?? 0),
        'invoiced'  => (int) ($stageRow->count_invoiced ?? 0),
    ];

    $matches = ($legacyCounts === $optCounts);
    if (!$matches) {
        $allPassed = false;
        echo "[$name] FAIL:\n  Legacy: " . json_encode($legacyCounts) . "\n  Opt:    " . json_encode($optCounts) . "\n";
    } else {
        echo "[$name] PASS: " . json_encode($optCounts) . "\n";
    }
}

if ($allPassed) {
    echo "\nALL SCENARIOS PASSED WITH 100% COUNT MATCH!\n";
} else {
    echo "\nSOME SCENARIOS FAILED!\n";
    exit(1);
}
