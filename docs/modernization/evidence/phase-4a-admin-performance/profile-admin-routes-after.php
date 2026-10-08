<?php
require __DIR__.'/phase4a-bootstrap.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

echo "=== ACCURATE PROFILING OF ADMIN CONTROLLERS (AFTER PHASE 4A) ===\n\n";

$queries = [];
DB::listen(function ($q) use (&$queries) {
    $queries[] = [
        'sql' => $q->sql,
        'time' => $q->time,
    ];
});

$adminControllers = [
    'Dashboard' => [
        'class' => \App\Http\Controllers\Admin\DashbaordController::class,
        'method' => '__invoke',
        'params' => [],
    ],
    'Bookings' => [
        'class' => \App\Http\Controllers\Admin\BookingController::class,
        'method' => 'index',
        'params' => [],
    ],
    'Companies' => [
        'class' => \App\Http\Controllers\Admin\CompanyController::class,
        'method' => 'index',
        'params' => [],
    ],
    'Cars' => [
        'class' => \App\Http\Controllers\Admin\CarController::class,
        'method' => 'index',
        'params' => [],
    ],
    'Drivers' => [
        'class' => \App\Http\Controllers\Admin\DriverController::class,
        'method' => 'index',
        'params' => [],
    ],
    'Receipts' => [
        'class' => \App\Http\Controllers\Admin\ReceiptController::class,
        'method' => 'index',
        'params' => [],
    ],
    'MoneyTransfers' => [
        'class' => \App\Http\Controllers\Admin\MoneyTransferController::class,
        'method' => 'index',
        'params' => [],
    ],
    'Containers' => [
        'class' => \App\Http\Controllers\Admin\ContainerController::class,
        'method' => 'index',
        'params' => [],
    ],
    'Agents' => [
        'class' => \App\Http\Controllers\Admin\AgentController::class,
        'method' => 'index',
        'params' => [],
    ],
    'Employees' => [
        'class' => \App\Http\Controllers\Admin\EmployeeController::class,
        'method' => 'index',
        'params' => [],
    ],
];

$profileResults = [];

foreach ($adminControllers as $name => $spec) {
    $queries = [];
    $memBefore = memory_get_usage();
    $t0 = hrtime(true);

    try {
        $controller = $app->make($spec['class']);
        $req = Request::create('http://localhost/admin/' . strtolower($name), 'GET', $spec['params']);
        if ($spec['method'] === '__invoke') {
            $response = $controller($req);
        } else {
            $method = $spec['method'];
            $response = $controller->$method($req);
        }

        $elapsedMs = (hrtime(true) - $t0) / 1e6;
        $memUsed = memory_get_usage() - $memBefore;

        $profileResults[$name] = [
            'status' => 'OK',
            'query_count' => count($queries),
            'total_query_time_ms' => round(array_sum(array_column($queries, 'time')), 2),
            'execution_time_ms' => round($elapsedMs, 2),
            'memory_used_kb' => round($memUsed / 1024, 2),
            'queries' => array_column($queries, 'sql'),
        ];

        echo sprintf("[%s] SUCCESS: %d queries | %.2f ms | %.2f KB\n", $name, count($queries), $elapsedMs, $memUsed / 1024);
    } catch (\Throwable $e) {
        $elapsedMs = (hrtime(true) - $t0) / 1e6;
        $profileResults[$name] = [
            'status' => 'ERROR',
            'error_message' => $e->getMessage(),
            'query_count' => count($queries),
            'execution_time_ms' => round($elapsedMs, 2),
            'queries_before_error' => array_column($queries, 'sql'),
        ];
        echo sprintf("[%s] ERROR: %s (%d queries)\n", $name, $e->getMessage(), count($queries));
    }
}

file_put_contents(
    __DIR__.'/admin-profile-inventory-after.json',
    json_encode($profileResults, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
);

echo "\nProfile written to admin-profile-inventory-after.json\n";
