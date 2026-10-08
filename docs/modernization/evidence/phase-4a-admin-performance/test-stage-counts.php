<?php
require __DIR__.'/phase4a-bootstrap.php';

use App\Models\Booking;
use Illuminate\Http\Request;

echo "=== TESTING STAGE COUNTS FOR INVOICE STATUS ===\n";

$req = new Request(['invoice_status' => '1']);

$legacyCounts = [
    'all'       => (clone Booking::query()->filterListing($req))->count(),
    'assigned'  => (clone Booking::query()->filterListing($req))->filterStage('assigned')->count(),
    'waiting'   => (clone Booking::query()->filterListing($req))->filterStage('waiting')->count(),
    'loading'   => (clone Booking::query()->filterListing($req))->filterStage('loading')->count(),
    'unloading' => (clone Booking::query()->filterListing($req))->filterStage('unloading')->count(),
    'invoiced'  => (clone Booking::query()->filterListing($req))->filterStage('invoiced')->count(),
];

$row = (clone Booking::query()->filterListing($req))
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
    'all'       => (int) ($row->count_all ?? 0),
    'assigned'  => (int) ($row->count_assigned ?? 0),
    'waiting'   => (int) ($row->count_waiting ?? 0),
    'loading'   => (int) ($row->count_loading ?? 0),
    'unloading' => (int) ($row->count_unloading ?? 0),
    'invoiced'  => (int) ($row->count_invoiced ?? 0),
];

echo "Legacy counts: " . json_encode($legacyCounts) . "\n";
echo "Opt counts:    " . json_encode($optCounts) . "\n";
echo "Match: " . (($legacyCounts === $optCounts) ? "YES (100% IDENTICAL)" : "NO") . "\n";
