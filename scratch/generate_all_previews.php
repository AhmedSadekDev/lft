<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

view()->share('errors', new \Illuminate\Support\ViewErrorBag);

$user = \App\Models\User::first() ?? new \App\Models\User([
    'name' => 'المدير العام',
    'email' => 'admin@leaderfortrans.com',
]);
$user->id = 1;
\Illuminate\Support\Facades\Auth::setUser($user);

$previewDir = __DIR__ . '/../docs/modernization/evidence/phase-4b-admin-ui-redesign/final-audit';
if (!is_dir($previewDir)) {
    mkdir($previewDir, 0777, true);
}

function processAndSaveHtml($filenameBase, $htmlContent, $targetDir) {
    // Replace production asset url with relative path to local public/assets
    $cleanHtml = str_replace('https://cloudymenue.cloudy-digital.com/assets/', '../../../../public/assets/', $htmlContent);
    // Replace root-relative /assets/
    $cleanHtml = str_replace('"/assets/', '"../../../../public/assets/', $cleanHtml);
    $cleanHtml = str_replace("'/assets/", "'../../../../public/assets/", $cleanHtml);
    
    // 1. Light mode
    $lightHtml = str_replace('<html lang="ar" dir="rtl">', '<html lang="ar" dir="rtl" data-theme="light">', $cleanHtml);
    file_put_contents($targetDir . "/{$filenameBase}-light.html", $lightHtml);
    
    // 2. Dark mode
    $darkHtml = str_replace('<html lang="ar" dir="rtl">', '<html lang="ar" dir="rtl" data-theme="dark">', $cleanHtml);
    if (strpos($darkHtml, 'data-theme="dark"') === false) {
        $darkHtml = str_replace('<html ', '<html data-theme="dark" ', $darkHtml);
    }
    file_put_contents($targetDir . "/{$filenameBase}-dark.html", $darkHtml);
    
    echo "Saved: {$filenameBase} (Light & Dark)\n";
}

// 1. Login Page
try {
    $loginHtml = view('auth.login')->render();
    processAndSaveHtml('01-login', $loginHtml, $previewDir);
} catch (\Throwable $e) {
    echo "Login error: " . $e->getMessage() . "\n";
}

// 2. Dashboard
try {
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
    processAndSaveHtml('02-dashboard', $dashHtml, $previewDir);
} catch (\Throwable $e) {
    echo "Dashboard error: " . $e->getMessage() . "\n";
}

// 3. Bookings Listing
try {
    $bookings = \App\Models\Booking::with(['company', 'factory', 'bookingContainers'])->latest()->paginate(10);
    $companies = \App\Models\Company::all();
    $stageCounts = [
        'all' => \App\Models\Booking::count(),
        'assigned' => 10,
        'waiting' => 12,
        'loading' => 8,
        'unloading' => 5,
        'invoiced' => 45,
    ];
    $bookingsHtml = view('admin.bookings.index', compact('bookings', 'companies', 'stageCounts'))->render();
    processAndSaveHtml('03-bookings-index', $bookingsHtml, $previewDir);
} catch (\Throwable $e) {
    echo "Bookings listing error: " . $e->getMessage() . "\n";
}

// 4. Booking Details (Show #471 or latest)
try {
    $booking = \App\Models\Booking::with(['company', 'bookingContainers', 'expenses', 'receipts', 'bookingServices'])->find(471) 
               ?? \App\Models\Booking::with(['company', 'bookingContainers', 'expenses', 'receipts', 'bookingServices'])->first();
    if ($booking) {
        $booking->expenses_count = $booking->expenses ? $booking->expenses->count() : 0;
        $deliveryPolices = \App\Models\DeliveryPolicy::whereHas('booking_containers', function($container) use($booking) {
            $container->where('booking_id', $booking->id);
        })->get();

        $input = [
            'booking' => $booking,
            'containers' => $booking->bookingContainers ? $booking->bookingContainers->mapWithKeys(function ($container) {
                return [$container->container?->id => $container->container?->type];
            }) : collect(),
            'classifications' => \App\Models\ServiceCategory::pluck('title', 'id'),
            'citiesAndRegions' => \App\Models\CitiesAndRegions::pluck('title', 'id'),
            'deliveryPolices' => $deliveryPolices
        ];
        $bookingHtml = view('admin.bookings.show', $input)->render();
        processAndSaveHtml('04-booking-show', $bookingHtml, $previewDir);
    }
} catch (\Throwable $e) {
    echo "Booking show error: " . $e->getMessage() . "\n";
}

// 5. Containers Listing
try {
    $containers = \App\Models\Container::latest()->paginate(10);
    $containersHtml = view('admin.containers.index', compact('containers'))->render();
    processAndSaveHtml('05-containers-index', $containersHtml, $previewDir);
} catch (\Throwable $e) {
    echo "Containers error: " . $e->getMessage() . "\n";
}

// 6. Companies Listing
try {
    $companies = \App\Models\Company::latest()->paginate(10);
    $companiesHtml = view('admin.companies.index', compact('companies'))->render();
    processAndSaveHtml('06-companies-index', $companiesHtml, $previewDir);
} catch (\Throwable $e) {
    echo "Companies error: " . $e->getMessage() . "\n";
}

// 7. Cars Listing
try {
    $cars = \App\Models\Car::latest()->paginate(10);
    $carsHtml = view('admin.cars.index', compact('cars'))->render();
    processAndSaveHtml('07-cars-index', $carsHtml, $previewDir);
} catch (\Throwable $e) {
    echo "Cars error: " . $e->getMessage() . "\n";
}

echo "All preview HTML files created in final-audit/.\n";
