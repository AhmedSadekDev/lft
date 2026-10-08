<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

view()->share('errors', new \Illuminate\Support\ViewErrorBag);

$user = new \App\Models\User([
    'name' => 'المدير العام',
    'email' => 'admin@leaderfortrans.com',
]);
$user->id = 1;
\Illuminate\Support\Facades\Auth::setUser($user);

$booking = \App\Models\Booking::with(['company', 'bookingContainers', 'expenses', 'receipts', 'bookingServices'])->find(471);
if (!$booking) {
    die("Booking 471 not found");
}

$booking->expenses_count = $booking->expenses->count();

$deliveryPolices = \App\Models\DeliveryPolicy::whereHas('booking_containers', function($container) use($booking) {
    $container->where('booking_id', $booking->id);
})->get();

$input = [
    'booking' => $booking,
    'containers' => $booking->bookingContainers->mapWithKeys(function ($container) {
        return [$container->container?->id => $container->container?->type];
    }),
    'classifications' => \App\Models\ServiceCategory::pluck('title', 'id'),
    'citiesAndRegions' => \App\Models\CitiesAndRegions::pluck('title', 'id'),
    'deliveryPolices' => $deliveryPolices
];

$evidenceDir = __DIR__ . '/../docs/modernization/evidence/phase-4b-admin-ui-redesign/final-dark-mode-repair';
if (!is_dir($evidenceDir)) {
    mkdir($evidenceDir, 0777, true);
}

try {
    $html = view('admin.bookings.show', $input)->render();
    $html = str_replace('https://cloudymenue.cloudy-digital.com/assets/', '../../../../../public/assets/', $html);
    
    file_put_contents($evidenceDir . '/booking-471-light.html', $html);
    
    $darkHtml = str_replace('<html lang="ar" dir="rtl">', '<html lang="ar" dir="rtl" data-theme="dark">', $html);
    if (strpos($darkHtml, 'data-theme="dark"') === false) {
        $darkHtml = str_replace('<html ', '<html data-theme="dark" ', $darkHtml);
    }
    file_put_contents($evidenceDir . '/booking-471-dark.html', $darkHtml);
    echo "Booking 471 views rendered successfully!\n";
} catch (\Throwable $e) {
    echo "Error rendering booking 471: " . $e->getMessage() . "\n" . $e->getFile() . ":" . $e->getLine() . "\n";
}
