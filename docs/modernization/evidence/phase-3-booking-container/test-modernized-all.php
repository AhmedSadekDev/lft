<?php
require __DIR__.'/phase3-bootstrap.php';

$queries = [];
$db->listen(function ($q) use (&$queries) {
    $queries[] = [
        'sql' => $q->sql,
        'time' => $q->time
    ];
});

$controller = $app->make(\App\Http\Controllers\Api\Superagent\BookingContainerController::class);

$scenarios = [
    ['name' => 'waiting_stage', 'params' => ['stage_type' => 'waiting']],
    ['name' => 'waiting_page_2', 'params' => ['stage_type' => 'waiting', 'page' => 2]],
];

$results = [];

foreach ($scenarios as $sc) {
    $queries = [];
    $request = \Illuminate\Http\Request::create('http://localhost/api/superagent/booking/missions/all', 'GET', $sc['params']);
    $response = $controller->all($request);
    $data = json_decode($response->getContent(), true);

    $results[$sc['name']] = [
        'status_code' => $response->getStatusCode(),
        'query_count' => count($queries),
        'total' => $data['data']['meta']['total'] ?? null,
        'per_page' => $data['data']['meta']['per_page'] ?? null,
        'current_page' => $data['data']['meta']['current_page'] ?? null,
        'items_count' => isset($data['data']['data']) ? count($data['data']['data']) : 0,
        'queries' => $queries
    ];
}

echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
