<?php
require __DIR__.'/verification-bootstrap.php';
$kind = $argv[2] ?? 'api';
$fixtures = ['api' => [App\Models\Company::class, 6], 'employees' => [App\Models\Employee::class, 8],
    'superagent' => [App\Models\Superagent::class, 3], 'agent' => [App\Models\Agent::class, 25]];
$tokens = [];
$actors = [];
foreach ($fixtures as $guard => [$class, $id]) {
    $actor = $class::find($id) ?? $class::orderBy('id')->first();
    if (!$actor) throw new RuntimeException('No existing actor for '.$guard);
    $tokens[$guard] = $app['tymon.jwt']->fromUser($actor);
    $actors[$guard] = ['model' => $class, 'id' => $actor->getKey()];
}
$app['tymon.jwt']->unsetToken();
$cases = [];
if ($kind === 'api') {
    $cases = [
        ['company_bookings', 'GET', '/api/profile/bookings', 'api'],
        ['employee_bookings', 'GET', '/api/profile/bookings', 'employees'],
        ['superagent_agents', 'POST', '/api/superagent/booking/fetch_agents', 'superagent'],
    ];
} else {
    foreach (['companies', 'private-companies', 'cars', 'drivers', 'bookings'] as $resource) {
        foreach (['api', 'employees', 'superagent', 'agent'] as $guard) {
            $cases[] = [$resource.'/'.$guard, 'GET', '/dashboard/'.$resource, $guard];
        }
    }
}
$active = false;
$queries = [];
$db->listen(function ($query) use (&$active, &$queries) {
    if ($active) {
        if (!preg_match('/^\s*(select|show|explain)\b/i', $query->sql)) throw new RuntimeException('Unexpected non-read statement');
        $queries[] = ['sql' => $query->sql, 'bindings' => $query->bindings, 'ms' => $query->time];
    }
});
$pdo = $db->getPdo();
$threadId = (int) $pdo->query('SELECT THREAD_ID FROM performance_schema.threads WHERE PROCESSLIST_ID=CONNECTION_ID()')->fetchColumn();
$counter = fn () => (int) $pdo->query("SELECT COALESCE(SUM(SUM_ROWS_EXAMINED),0) FROM performance_schema.events_statements_summary_by_thread_by_event_name WHERE THREAD_ID=".$threadId)->fetchColumn();
$c1 = $counter();
$c2 = $counter();
$counterOverhead = $c2 - $c1;
$out = ['mode' => $mode, 'kind' => $kind, 'actors' => $actors, 'cases' => [], 'normalizations' => [],
    'auth_method' => 'Signed transient JWT from existing row; guards reset before kernel handle. No setUser, replacement account or middleware bypass.',
    'isolation' => 'Server-enforced session READ ONLY; real middleware; array cache/session; rollback; no termination hooks'];
foreach ($cases as [$name, $method, $uri, $guard]) {
    $runs = [];
    $body = '';
    $lastQueries = [];
    for ($i = 0; $i < ($kind === 'api' ? 6 : 1); $i++) {
        $app['auth']->forgetGuards();
        $app['auth']->shouldUse('web');
        $app['tymon.jwt']->unsetToken();
        $request = Illuminate\Http\Request::create('http://localhost'.$uri, $method, [], [], [],
            ['HTTP_ACCEPT' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer '.$tokens[$guard]]);
        $queries = [];
        if (function_exists('memory_reset_peak_usage')) memory_reset_peak_usage();
        $rowsBefore = $counter();
        $startMemory = memory_get_usage();
        $start = hrtime(true);
        $active = true;
        $response = $kernel->handle($request);
        $body = $response->getContent();
        $active = false;
        $elapsed = (hrtime(true)-$start)/1e6;
        $peakDelta = memory_get_peak_usage()-$startMemory;
        $rowsAfter = $counter();
        $runs[] = ['status' => $response->getStatusCode(), 'queries' => count($queries),
            'latency_ms' => $elapsed, 'peak_delta_bytes' => $peakDelta,
            'rows_examined_raw_delta' => $rowsAfter-$rowsBefore, 'counter_overhead' => $counterOverhead,
            'rows_examined' => $rowsAfter-$rowsBefore-$counterOverhead,
            'payload_bytes' => strlen($body), 'sha256' => hash('sha256', $body),
            'content_type' => $response->headers->get('Content-Type')];
        $lastQueries = $queries;
    }
    $plans = [];
    foreach ($lastQueries as $query) {
        if (!preg_match('/^\s*select\b/i', $query['sql'])) continue;
        $key = hash('sha256', $query['sql'].json_encode($query['bindings']));
        if (isset($plans[$key])) continue;
        $plans[$key] = ['sql' => $query['sql'], 'bindings' => $query['bindings'],
            'explain' => $db->select('EXPLAIN '.$query['sql'], $query['bindings'])];
    }
    $json = json_decode($body, true);
    $out['cases'][] = ['name' => $name, 'method' => $method, 'uri' => $uri, 'guard' => $guard,
        'runs' => $runs, 'queries' => $lastQueries, 'plans' => array_values($plans),
        'json_top_keys' => is_array($json) ? array_keys($json) : null,
        'data_count' => is_array($json['data'] ?? null) ? count($json['data']) : null];
}
echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
