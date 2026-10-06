<?php
require __DIR__.'/phase3-bootstrap.php';

$queries = [];
$db->listen(function ($query) use (&$queries) {
    $queries[] = [
        'sql' => $query->sql,
        'bindings' => $query->bindings,
        'time' => $query->time,
    ];
});

$controller = $app->make(\App\Http\Controllers\Api\Superagent\BookingContainerController::class);

$scenarios = [
    ['name' => 'all_default', 'params' => []],
    ['name' => 'stage_spec', 'params' => ['stage_type' => 'specification']],
    ['name' => 'stage_waiting', 'params' => ['stage_type' => 'waiting']],
    ['name' => 'stage_loading', 'params' => ['stage_type' => 'loading']],
    ['name' => 'stage_unloading', 'params' => ['stage_type' => 'unloading']],
    ['name' => 'page_2', 'params' => ['per_page' => 10, 'page' => 2]],
    ['name' => 'out_of_range_page', 'params' => ['per_page' => 100, 'page' => 999]],
];

$results = [];

foreach ($scenarios as $sc) {
    $queries = [];
    $request = \Illuminate\Http\Request::create('http://localhost/api/superagent/booking/missions/all', 'GET', $sc['params']);
    $response = $controller->all($request);
    $data = json_decode($response->getContent(), true);

    $results[$sc['name']] = [
        'status' => $response->getStatusCode(),
        'query_count' => count($queries),
        'total' => $data['data']['meta']['total'] ?? ($data['data']['total'] ?? null),
        'per_page' => $data['data']['meta']['per_page'] ?? ($data['data']['per_page'] ?? null),
        'current_page' => $data['data']['meta']['current_page'] ?? ($data['data']['current_page'] ?? null),
        'data_count' => isset($data['data']['data']) ? count($data['data']['data']) : (isset($data['data']) && is_array($data['data']) ? count($data['data']) : null),
        'top_keys' => is_array($data) ? array_keys($data) : [],
        'data_keys' => isset($data['data']) && is_array($data['data']) ? array_keys($data['data']) : [],
        'sample_booking_ids' => isset($data['data']['data']) ? array_map(fn($b) => $b['id'] ?? null, array_slice($data['data']['data'], 0, 5)) : [],
        'queries_summary' => array_slice($queries, 0, 8),
    ];
}

echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
