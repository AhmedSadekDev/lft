<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$bcCount = \App\Models\BookingContainer::count();
$statuses = \App\Models\BookingContainer::selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status')->toArray();
$recentBookings = \App\Models\Booking::with('company')->latest()->take(5)->get(['id', 'booking_number', 'company_id', 'created_at']);

echo json_encode([
    'total_bc' => $bcCount,
    'statuses' => $statuses,
    'recent' => $recentBookings->toArray()
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
