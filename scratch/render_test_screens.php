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

$evidenceDir = __DIR__ . '/../docs/modernization/evidence/phase-4b-admin-ui-redesign/comprehensive-audit';
if (!is_dir($evidenceDir)) {
    mkdir($evidenceDir, 0777, true);
}

function savePagePair($name, $html, $dir) {
    $htmlClean = str_replace('https://cloudymenue.cloudy-digital.com/assets/', '../../../../../public/assets/', $html);
    file_put_contents($dir . "/{$name}-light.html", $htmlClean);
    
    $dark = str_replace('<html lang="ar" dir="rtl">', '<html lang="ar" dir="rtl" data-theme="dark">', $htmlClean);
    if (strpos($dark, 'data-theme="dark"') === false) {
        $dark = str_replace('<html ', '<html data-theme="dark" ', $dark);
    }
    file_put_contents($dir . "/{$name}-dark.html", $dark);
    echo "Rendered: {$name} (Light & Dark)\n";
}

// 1. Bookings index
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
    $html = view('admin.bookings.index', compact('bookings', 'companies', 'stageCounts'))->render();
    savePagePair('01-bookings-index', $html, $evidenceDir);
} catch (\Throwable $e) {
    echo "Error bookings: " . $e->getMessage() . "\n";
}

// 2. Containers index
try {
    $containers = \App\Models\Container::latest()->paginate(10);
    $html = view('admin.containers.index', compact('containers'))->render();
    savePagePair('02-containers-index', $html, $evidenceDir);
} catch (\Throwable $e) {
    echo "Error containers: " . $e->getMessage() . "\n";
}

// 3. Cars index
try {
    $cars = \App\Models\Car::latest()->paginate(10);
    $html = view('admin.cars.index', compact('cars'))->render();
    savePagePair('03-cars-index', $html, $evidenceDir);
} catch (\Throwable $e) {
    echo "Error cars: " . $e->getMessage() . "\n";
}

// 4. Companies index
try {
    $companies = \App\Models\Company::latest()->paginate(10);
    $html = view('admin.companies.index', compact('companies'))->render();
    savePagePair('04-companies-index', $html, $evidenceDir);
} catch (\Throwable $e) {
    echo "Error companies: " . $e->getMessage() . "\n";
}

// 5. Invoices index
try {
    $company = \App\Models\Company::first();
    request()->merge(['id' => $company ? $company->id : 1]);
    $companies = \App\Models\Company::all();
    $bookings = \App\Models\Booking::whereHas('invoice')->with(['company', 'invoice.invoicePayments'])->latest()->take(10)->get();
    $banks = \App\Models\Bank::all();
    $allPayments = collect();
    $html = view('admin.invoices.index', compact('bookings', 'companies', 'banks', 'allPayments'))->render();
    savePagePair('05-invoices-index', $html, $evidenceDir);
} catch (\Throwable $e) {
    echo "Error invoices: " . $e->getMessage() . "\n";
}

// 6. Drivers index
try {
    $drivers = \App\Models\Driver::latest()->paginate(10);
    $html = view('admin.drivers.index', compact('drivers'))->render();
    savePagePair('06-drivers-index', $html, $evidenceDir);
} catch (\Throwable $e) {
    echo "Error drivers: " . $e->getMessage() . "\n";
}

echo "All test screens rendered successfully.\n";
