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

$evidenceDir = __DIR__ . '/../docs/modernization/evidence/phase-4b-admin-ui-redesign/final-dark-mode-repair';
if (!is_dir($evidenceDir)) {
    mkdir($evidenceDir, 0777, true);
}

// 1. Booking Details (471)
$booking = \App\Models\Booking::with(['company', 'bookingContainers', 'expenses', 'receipts', 'bookingServices'])->find(471);
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

$b471Html = view('admin.bookings.show', $input)->render();
$b471Html = str_replace('https://cloudymenue.cloudy-digital.com/assets/', '../../../../../public/assets/', $b471Html);

file_put_contents($evidenceDir . '/booking-471-light.html', $b471Html);

$b471Dark = str_replace('<html lang="ar" dir="rtl">', '<html lang="ar" dir="rtl" data-theme="dark">', $b471Html);
if (strpos($b471Dark, 'data-theme="dark"') === false) {
    $b471Dark = str_replace('<html ', '<html data-theme="dark" ', $b471Dark);
}
file_put_contents($evidenceDir . '/booking-471-dark.html', $b471Dark);
echo "1. Booking 471 (Light & Dark) rendered\n";

// 2. Booking Listing (Dark)
$bookings = \App\Models\Booking::with('company')->latest()->paginate(10);
$companies = \App\Models\Company::all();
$stageCounts = [];
$currentStage = null;
$listHtml = view('admin.bookings.index', compact('bookings', 'companies', 'stageCounts', 'currentStage'))->render();
$listHtml = str_replace('https://cloudymenue.cloudy-digital.com/assets/', '../../../../../public/assets/', $listHtml);
$listDark = str_replace('<html lang="ar" dir="rtl">', '<html lang="ar" dir="rtl" data-theme="dark">', $listHtml);
if (strpos($listDark, 'data-theme="dark"') === false) {
    $listDark = str_replace('<html ', '<html data-theme="dark" ', $listDark);
}
file_put_contents($evidenceDir . '/booking-listing-dark.html', $listDark);
echo "2. Booking listing (Dark) rendered\n";

// 3. Dashboard Homepage (Dark)
$stats = [
    'total_bookings' => 476,
    'today_bookings' => 12,
    'week_bookings' => 45,
    'month_bookings' => 120,
    'total_containers' => 892,
    'today_containers' => 14,
    'total_agents' => 18,
    'total_superagents' => 4,
    'total_companies' => 43,
    'total_cars' => 124,
    'total_drivers' => 95,
    'vault_amount' => 1250000,
    'today_expenses' => 14500,
    'today_income' => 28000,
    'month_expenses' => 214979,
    'month_income' => 264100,
    'total_delivery_policies' => 312,
    'today_delivery_policies' => 8,
    'total_invoices' => 410,
    'today_invoices' => 15,
    'month_invoices' => 135,
    'checks_due_within_3_days' => 5,
];

$bookingsChart = [
    'labels' => ['مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر'],
    'data' => [45, 60, 110, 95, 130, 155]
];

$financialChart = [
    'labels' => ['مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر'],
    'expenses' => [180000, 195000, 210000, 205000, 220000, 214979],
    'income' => [210000, 225000, 245000, 240000, 260000, 264100]
];

$dashHtml = view('admin.index', compact('stats', 'bookingsChart', 'financialChart'))->render();
$dashHtml = str_replace('https://cloudymenue.cloudy-digital.com/assets/', '../../../../../public/assets/', $dashHtml);
$dashDark = str_replace('<html lang="ar" dir="rtl">', '<html lang="ar" dir="rtl" data-theme="dark">', $dashHtml);
if (strpos($dashDark, 'data-theme="dark"') === false) {
    $dashDark = str_replace('<html ', '<html data-theme="dark" ', $dashDark);
}
file_put_contents($evidenceDir . '/dashboard-dark.html', $dashDark);
echo "3. Dashboard (Dark) rendered\n";

// 4. Sidebar Collapsed (Dark)
$collapsedDark = str_replace('aside-enabled', 'aside-enabled aside-minimize', $dashDark);
file_put_contents($evidenceDir . '/sidebar-collapsed-dark.html', $collapsedDark);
echo "4. Sidebar Collapsed (Dark) rendered\n";
