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

$evidenceDir = __DIR__ . '/../docs/modernization/evidence/phase-4b-admin-ui-redesign/dark-mode-refinement';
if (!is_dir($evidenceDir)) {
    mkdir($evidenceDir, 0777, true);
}

// 1. Containers Listing
try {
    $containers = \App\Models\Container::latest()->paginate(10);
    $containersHtml = view('admin.containers.index', compact('containers'))->render();
    $containersHtml = str_replace('https://cloudymenue.cloudy-digital.com/assets/', '../../../../../public/assets/', $containersHtml);
    
    file_put_contents($evidenceDir . '/containers-light.html', $containersHtml);
    
    $containersDark = str_replace('<html lang="ar" dir="rtl">', '<html lang="ar" dir="rtl" data-theme="dark">', $containersHtml);
    if (strpos($containersDark, 'data-theme="dark"') === false) {
        $containersDark = str_replace('<html ', '<html data-theme="dark" ', $containersDark);
    }
    file_put_contents($evidenceDir . '/containers-dark.html', $containersDark);
    echo "1. Containers index rendered\n";
} catch (\Throwable $e) {
    echo "Error rendering containers: " . $e->getMessage() . "\n";
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
    $dashHtml = str_replace('https://cloudymenue.cloudy-digital.com/assets/', '../../../../../public/assets/', $dashHtml);
    
    file_put_contents($evidenceDir . '/dashboard-light.html', $dashHtml);
    $dashDark = str_replace('<html lang="ar" dir="rtl">', '<html lang="ar" dir="rtl" data-theme="dark">', $dashHtml);
    if (strpos($dashDark, 'data-theme="dark"') === false) {
        $dashDark = str_replace('<html ', '<html data-theme="dark" ', $dashDark);
    }
    file_put_contents($evidenceDir . '/dashboard-dark.html', $dashDark);
    echo "2. Dashboard rendered\n";
} catch (\Throwable $e) {
    echo "Error rendering dashboard: " . $e->getMessage() . "\n";
}

// 3. Container Create Form
try {
    $action = route('containers.store');
    $method = 'POST';
    $formHtml = view('admin.containers.create', compact('action', 'method'))->render();
    $formHtml = str_replace('https://cloudymenue.cloudy-digital.com/assets/', '../../../../../public/assets/', $formHtml);
    
    file_put_contents($evidenceDir . '/container-form-light.html', $formHtml);
    $formDark = str_replace('<html lang="ar" dir="rtl">', '<html lang="ar" dir="rtl" data-theme="dark">', $formHtml);
    if (strpos($formDark, 'data-theme="dark"') === false) {
        $formDark = str_replace('<html ', '<html data-theme="dark" ', $formDark);
    }
    file_put_contents($evidenceDir . '/container-form-dark.html', $formDark);
    echo "3. Container form rendered\n";
} catch (\Throwable $e) {
    echo "Error rendering form: " . $e->getMessage() . "\n";
}
