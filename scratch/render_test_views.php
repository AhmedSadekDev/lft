<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$evidenceDir = __DIR__ . '/../docs/modernization/evidence/phase-4b-admin-ui-redesign';
if (!is_dir($evidenceDir)) {
    mkdir($evidenceDir, 0777, true);
}

// 1. Render Login View
try {
    $loginHtml = view('auth.login')->render();
    file_put_contents($evidenceDir . '/login-rendered.html', $loginHtml);
    echo "Login view rendered successfully (" . strlen($loginHtml) . " bytes)\n";
} catch (\Throwable $e) {
    echo "Error rendering login: " . $e->getMessage() . "\n";
}

// 2. Render Dashboard View with mock User
try {
    $user = new \App\Models\User([
        'name' => 'المدير العام',
        'email' => 'admin@leaderfortrans.com',
    ]);
    $user->id = 1;
    \Illuminate\Support\Facades\Auth::setUser($user);

    // Mock permissions if needed
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

    $dashboardHtml = view('admin.index', compact('stats', 'bookingsChart', 'financialChart'))->render();
    file_put_contents($evidenceDir . '/dashboard-rendered.html', $dashboardHtml);
    echo "Dashboard view rendered successfully (" . strlen($dashboardHtml) . " bytes)\n";
} catch (\Throwable $e) {
    echo "Error rendering dashboard: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\n";
}
