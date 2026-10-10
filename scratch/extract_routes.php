<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$routes = Illuminate\Support\Facades\Route::getRoutes();

$agentRoutes = [];
$superagentRoutes = [];

foreach ($routes as $route) {
    $uri = $route->uri();
    $methods = implode('|', array_diff($route->methods(), ['HEAD']));
    $action = $route->getActionName();
    
    // Clean action
    $actionShort = str_replace('App\\Http\\Controllers\\Api\\', '', $action);

    if (str_starts_with($uri, 'api/agent/')) {
        $agentRoutes[] = [
            'method' => $methods,
            'uri' => '/' . $uri,
            'action' => $actionShort,
        ];
    } elseif (str_starts_with($uri, 'api/superagent/')) {
        $superagentRoutes[] = [
            'method' => $methods,
            'uri' => '/' . $uri,
            'action' => $actionShort,
        ];
    }
}

echo "Agent Routes Total: " . count($agentRoutes) . "\n";
echo "Superagent Routes Total: " . count($superagentRoutes) . "\n";
file_put_contents(__DIR__ . '/all_agent_routes.json', json_encode($agentRoutes, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
file_put_contents(__DIR__ . '/all_superagent_routes.json', json_encode($superagentRoutes, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
