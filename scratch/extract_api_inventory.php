<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Route;

$routes = Route::getRoutes()->getRoutes();

$inventory = [];

foreach ($routes as $route) {
    $uri = $route->uri();
    if (!str_starts_with($uri, 'api')) {
        continue;
    }

    $methods = array_filter($route->methods(), fn($m) => $m !== 'HEAD');
    $actionName = $route->getActionName();
    $middleware = $route->middleware();

    // Grouping
    $group = 'Other API';
    if (str_starts_with($uri, 'api/superagent')) {
        $group = 'Superagent API';
    } elseif (str_starts_with($uri, 'api/agent')) {
        $group = 'Agent API';
    } elseif (str_starts_with($uri, 'api/desktop')) {
        $group = 'Desktop Invoicing API';
    } elseif (str_starts_with($uri, 'api/company') || str_starts_with($uri, 'api/client-portal') || str_contains($uri, 'client_portal')) {
        $group = 'Client Portal API';
    } elseif (str_starts_with($uri, 'api/track') || str_starts_with($uri, 'api/orders')) {
        $group = 'Public Tracking & Order API';
    }

    $inventory[] = [
        'method' => implode('|', $methods),
        'uri' => '/' . $uri,
        'action' => $actionName,
        'middleware' => implode(', ', $middleware),
        'group' => $group,
    ];
}

// Sort by group and uri
usort($inventory, function ($a, $b) {
    if ($a['group'] === $b['group']) {
        return strcmp($a['uri'], $b['uri']);
    }
    return strcmp($a['group'], $b['group']);
});

file_put_contents('scratch/api_inventory_raw.json', json_encode($inventory, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

echo "TOTAL_API_ROUTES: " . count($inventory) . PHP_EOL;

$counts = array_count_values(array_column($inventory, 'group'));
foreach ($counts as $grp => $cnt) {
    echo "  - {$grp}: {$cnt}\n";
}
