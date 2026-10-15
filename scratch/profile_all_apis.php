<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
use Illuminate\Http\Request;

$app->instance('request', Request::create('/', 'GET'));
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

// Sample IDs
$agent = App\Models\Agent::first();
$superagent = App\Models\Superagent::first();
$company = App\Models\Company::first();
$booking = App\Models\Booking::first();
$container = App\Models\BookingContainer::first();

$endpointsToProfile = [
    // --- 1. Agent APIs ---
    ['group' => 'Agent API', 'method' => 'GET', 'uri' => '/api/agent/booking/fetch_home_statistics', 'guard' => 'agent', 'actor' => $agent, 'params' => []],
    ['group' => 'Agent API', 'method' => 'POST', 'uri' => '/api/agent/booking/fetch_bookings', 'guard' => 'agent', 'actor' => $agent, 'params' => ['page' => 1, 'per_page' => 20]],
    ['group' => 'Agent API', 'method' => 'GET', 'uri' => '/api/agent/booking/fetch_loading_assignments', 'guard' => 'agent', 'actor' => $agent, 'params' => []],
    ['group' => 'Agent API', 'method' => 'GET', 'uri' => '/api/agent/booking/fetch_specification_assignments', 'guard' => 'agent', 'actor' => $agent, 'params' => []],
    ['group' => 'Agent API', 'method' => 'GET', 'uri' => '/api/agent/booking/fetch_unloading_assignments', 'guard' => 'agent', 'actor' => $agent, 'params' => []],
    ['group' => 'Agent API', 'method' => 'POST', 'uri' => '/api/agent/fetch_your_notifications', 'guard' => 'agent', 'actor' => $agent, 'params' => ['page' => 1, 'per_page' => 20]],
    ['group' => 'Agent API', 'method' => 'GET', 'uri' => '/api/agent/photos', 'guard' => 'agent', 'actor' => $agent, 'params' => ['per_page' => 20]],
    ['group' => 'Agent API', 'method' => 'GET', 'uri' => '/api/agent/fetch_delivery_policies', 'guard' => 'agent', 'actor' => $agent, 'params' => ['page' => 1, 'per_page' => 20]],
    ['group' => 'Agent API', 'method' => 'POST', 'uri' => '/api/agent/fetch_all_expenses', 'guard' => 'agent', 'actor' => $agent, 'params' => ['page' => 1, 'per_page' => 20]],
    ['group' => 'Agent API', 'method' => 'POST', 'uri' => '/api/agent/fetch_agents', 'guard' => 'agent', 'actor' => $agent, 'params' => ['page' => 1, 'per_page' => 20]],
    ['group' => 'Agent API', 'method' => 'GET', 'uri' => '/api/agent/booking/fetch_yard_bookings', 'guard' => 'agent', 'actor' => $agent, 'params' => ['yard_id' => 1, 'page' => 1]],
    ['group' => 'Agent API', 'method' => 'GET', 'uri' => '/api/agent/wallets', 'guard' => 'agent', 'actor' => $agent, 'params' => []],
    ['group' => 'Agent API', 'method' => 'GET', 'uri' => '/api/agent/fetch_profile', 'guard' => 'agent', 'actor' => $agent, 'params' => []],

    // --- 2. Superagent APIs ---
    ['group' => 'Superagent API', 'method' => 'GET', 'uri' => '/api/superagent/fetch_home', 'guard' => 'superagent', 'actor' => $superagent, 'params' => []],
    ['group' => 'Superagent API', 'method' => 'GET', 'uri' => '/api/superagent/booking/specification', 'guard' => 'superagent', 'actor' => $superagent, 'params' => ['page' => 1, 'per_page' => 20]],
    ['group' => 'Superagent API', 'method' => 'GET', 'uri' => '/api/superagent/booking/waiting', 'guard' => 'superagent', 'actor' => $superagent, 'params' => ['page' => 1, 'per_page' => 20]],
    ['group' => 'Superagent API', 'method' => 'GET', 'uri' => '/api/superagent/booking/loading', 'guard' => 'superagent', 'actor' => $superagent, 'params' => ['page' => 1, 'per_page' => 20]],
    ['group' => 'Superagent API', 'method' => 'GET', 'uri' => '/api/superagent/booking/unloading', 'guard' => 'superagent', 'actor' => $superagent, 'params' => ['page' => 1, 'per_page' => 20]],
    ['group' => 'Superagent API', 'method' => 'GET', 'uri' => '/api/superagent/booking/all', 'guard' => 'superagent', 'actor' => $superagent, 'params' => ['page' => 1, 'per_page' => 20]],
    ['group' => 'Superagent API', 'method' => 'POST', 'uri' => '/api/superagent/fetch_your_notifications', 'guard' => 'superagent', 'actor' => $superagent, 'params' => ['page' => 1, 'per_page' => 20]],
    ['group' => 'Superagent API', 'method' => 'POST', 'uri' => '/api/superagent/booking/fetch_agents', 'guard' => 'superagent', 'actor' => $superagent, 'params' => ['page' => 1, 'per_page' => 20]],
    ['group' => 'Superagent API', 'method' => 'GET', 'uri' => '/api/superagent/booking/fetch_yard_bookings', 'guard' => 'superagent', 'actor' => $superagent, 'params' => ['yard_id' => 1, 'page' => 1]],
    ['group' => 'Superagent API', 'method' => 'GET', 'uri' => '/api/superagent/booking/pending_stage_receipts', 'guard' => 'superagent', 'actor' => $superagent, 'params' => ['page' => 1, 'per_page' => 20]],
    ['group' => 'Superagent API', 'method' => 'GET', 'uri' => '/api/superagent/booking/receipts_count', 'guard' => 'superagent', 'actor' => $superagent, 'params' => []],
    ['group' => 'Superagent API', 'method' => 'GET', 'uri' => '/api/superagent/booking/receipt_summary', 'guard' => 'superagent', 'actor' => $superagent, 'params' => []],

    // --- 3. Desktop APIs ---
    ['group' => 'Desktop Invoicing API', 'method' => 'GET', 'uri' => '/api/desktop/orders/all', 'guard' => 'none', 'actor' => null, 'params' => ['page' => 1, 'per_page' => 20]],
    ['group' => 'Desktop Invoicing API', 'method' => 'GET', 'uri' => '/api/desktop/orders/' . ($booking?->booking_number ?? 'EBKG18440736'), 'guard' => 'none', 'actor' => null, 'params' => []],
    ['group' => 'Desktop Invoicing API', 'method' => 'GET', 'uri' => '/api/desktop/orders/' . ($booking?->booking_number ?? 'EBKG18440736') . '/container/' . ($container?->id ?? 1), 'guard' => 'none', 'actor' => null, 'params' => []],
    ['group' => 'Desktop Invoicing API', 'method' => 'GET', 'uri' => '/api/desktop/orders/' . ($booking?->booking_number ?? 'EBKG18440736') . '/papers', 'guard' => 'none', 'actor' => null, 'params' => []],

    // --- 4. Public Tracking & Client Portal APIs ---
    ['group' => 'Public Tracking & Order API', 'method' => 'GET', 'uri' => '/api/track', 'guard' => 'none', 'actor' => null, 'params' => ['order_number' => $booking?->booking_number ?? 'EBKG18440736']],
    ['group' => 'Public Tracking & Order API', 'method' => 'GET', 'uri' => '/api/settings', 'guard' => 'none', 'actor' => null, 'params' => []],
    ['group' => 'Public Tracking & Order API', 'method' => 'GET', 'uri' => '/api/services', 'guard' => 'none', 'actor' => null, 'params' => []],
    ['group' => 'Client Portal API', 'method' => 'GET', 'uri' => '/api/profile/bookings', 'guard' => 'api', 'actor' => $company, 'params' => ['page' => 1, 'per_page' => 20]],
];

$currentQueries = [];
$currentQueryTime = 0.0;

DB::listen(function ($query) use (&$currentQueries, &$currentQueryTime) {
    $currentQueries[] = [
        'sql' => $query->sql,
        'time' => $query->time,
    ];
    $currentQueryTime += $query->time;
});

foreach ($endpointsToProfile as $ep) {
    $uri = $ep['uri'];
    $method = $ep['method'];
    $guard = $ep['guard'];
    $actor = $ep['actor'];
    $params = $ep['params'];

    // Setup Auth
    auth()->forgetGuards();
    if ($guard !== 'none' && $actor) {
        auth($guard)->login($actor);
    }

    $currentQueries = [];
    $currentQueryTime = 0.0;

    $memBefore = memory_get_usage();
    $start = hrtime(true);

    try {
        $request = Request::create($uri, $method, $params);
        $request->headers->set('Accept', 'application/json');

        if ($guard !== 'none' && $actor) {
            $request->setUserResolver(fn() => $actor);
        }

        // Run through kernel / router
        $response = $app->handle($request);
        $end = hrtime(true);
        $memPeak = memory_get_peak_usage() - $memBefore;

        $totalTimeMs = ($end - $start) / 1e6;
        $status = $response->getStatusCode();
        $payloadSize = strlen($response->getContent());

        // Analyze duplicates
        $sqlList = array_column($currentQueries, 'sql');
        $counts = array_count_values($sqlList);
        $duplicates = 0;
        foreach ($counts as $sql => $c) {
            if ($c > 1) {
                $duplicates += ($c - 1);
            }
        }

        // Slowest query
        $slowest = null;
        if (!empty($currentQueries)) {
            usort($currentQueries, fn($a, $b) => $b['time'] <=> $a['time']);
            $slowest = $currentQueries[0];
        }

        $phpTimeMs = max(0, $totalTimeMs - $currentQueryTime);

        $priority = 'P3';
        if ($totalTimeMs > 400 || count($currentQueries) >= 20 || $duplicates >= 5) {
            $priority = 'P0';
        } elseif ($totalTimeMs > 200 || count($currentQueries) >= 10 || $duplicates >= 2) {
            $priority = 'P1';
        } elseif ($totalTimeMs > 100 || count($currentQueries) >= 5) {
            $priority = 'P2';
        }

        $profileResults[] = [
            'group' => $ep['group'],
            'method' => $method,
            'uri' => $uri,
            'status' => $status,
            'total_ms' => round($totalTimeMs, 2),
            'sql_ms' => round($currentQueryTime, 2),
            'php_ms' => round($phpTimeMs, 2),
            'query_count' => count($currentQueries),
            'duplicates' => $duplicates,
            'memory_kb' => round($memPeak / 1024, 1),
            'payload_bytes' => $payloadSize,
            'slowest_query' => $slowest ? substr($slowest['sql'], 0, 120) . '... (' . $slowest['time'] . 'ms)' : null,
            'priority' => $priority,
        ];
    } catch (\Throwable $e) {
        $profileResults[] = [
            'group' => $ep['group'],
            'method' => $method,
            'uri' => $uri,
            'status' => 'ERROR: ' . $e->getMessage(),
            'total_ms' => 0,
            'sql_ms' => 0,
            'php_ms' => 0,
            'query_count' => 0,
            'duplicates' => 0,
            'memory_kb' => 0,
            'payload_bytes' => 0,
            'slowest_query' => null,
            'priority' => 'BLOCKED',
        ];
    } finally {
        // Disconnect DB listener by rebooting/clearing
    }
}

file_put_contents('scratch/api_profile_baseline.json', json_encode($profileResults, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

echo "PROFILING_COMPLETED: " . count($profileResults) . " endpoints measured.\n\n";

// Print Markdown summary
echo "| Priority | Method | URI | Total Time | SQL Time | PHP Time | Queries | Duplicates | Status |\n";
echo "|:---:|:---:|---|:---:|:---:|:---:|:---:|:---:|:---:|\n";
foreach ($profileResults as $r) {
    if ($r['priority'] === 'BLOCKED') {
        echo "| {$r['priority']} | {$r['method']} | `{$r['uri']}` | N/A | N/A | N/A | N/A | N/A | {$r['status']} |\n";
    } else {
        echo "| **{$r['priority']}** | `{$r['method']}` | `{$r['uri']}` | {$r['total_ms']} ms | {$r['sql_ms']} ms | {$r['php_ms']} ms | {$r['query_count']} | {$r['duplicates']} | {$r['status']} |\n";
    }
}
