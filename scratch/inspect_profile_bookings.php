<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->instance('request', \Illuminate\Http\Request::create('/', 'GET'));
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

$company = App\Models\Company::first();
$queries = [];
\Illuminate\Support\Facades\DB::listen(function($q) {
    global $queries;
    $queries[] = $q->sql;
});

auth('api')->login($company);
$request = \Illuminate\Http\Request::create('/api/profile/bookings', 'GET', ['page' => 1, 'per_page' => 20]);
$request->headers->set('Accept', 'application/json');
$request->setUserResolver(fn() => $company);
$response = $app->handle($request);

echo "Total queries: " . count($queries) . "\n";
$counts = array_count_values($queries);
arsort($counts);
foreach (array_slice($counts, 0, 15) as $sql => $count) {
    echo "[$count times] " . substr($sql, 0, 120) . "...\n";
}
