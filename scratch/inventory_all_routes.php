<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Route;

$routes = Route::getRoutes();

$agentRoutes = [];
$superagentRoutes = [];

foreach ($routes as $route) {
    $uri = $route->uri();
    $methods = implode('|', array_diff($route->methods(), ['HEAD']));
    $action = $route->getActionName();

    if (str_starts_with($uri, 'api/agent')) {
        $agentRoutes[] = [
            'method' => $methods,
            'uri' => '/' . $uri,
            'action' => $action,
        ];
    } elseif (str_starts_with($uri, 'api/superagent')) {
        $superagentRoutes[] = [
            'method' => $methods,
            'uri' => '/' . $uri,
            'action' => $action,
        ];
    }
}

echo "AGENT ROUTES COUNT: " . count($agentRoutes) . "\n";
echo "SUPERAGENT ROUTES COUNT: " . count($superagentRoutes) . "\n";

file_put_contents(__DIR__ . '/agent_routes.json', json_encode($agentRoutes, JSON_PRETTY_PRINT));
file_put_contents(__DIR__ . '/superagent_routes.json', json_encode($superagentRoutes, JSON_PRETTY_PRINT));
echo "Saved routes to json files.\n";
