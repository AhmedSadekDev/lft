<?php
require __DIR__.'/phase4a-bootstrap.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

$queries = [];
DB::listen(function ($q) {
    $GLOBALS['queries'][] = [
        'sql' => $q->sql,
        'bindings' => $q->bindings,
        'time' => $q->time,
    ];
});

$controller = $app->make(\App\Http\Controllers\Admin\BookingController::class);
$req = Request::create('http://localhost/admin/bookings', 'GET');
$view = $controller->index($req);

echo "Total queries in controller: " . count($GLOBALS['queries']) . "\n";
foreach ($GLOBALS['queries'] as $i => $q) {
    echo ($i + 1) . ". [{$q['time']}ms] " . $q['sql'] . "\n";
}

// Now render the view if possible to see N+1 during Blade rendering!
echo "\n--- Rendering Blade View ---\n";
$viewQueriesBefore = count($GLOBALS['queries']);
try {
    $html = $view->render();
    $viewQueriesAfter = count($GLOBALS['queries']);
    echo "Queries during Blade render: " . ($viewQueriesAfter - $viewQueriesBefore) . "\n";
    echo "Total combined queries: $viewQueriesAfter\n";
    if ($viewQueriesAfter > $viewQueriesBefore) {
        echo "View queries:\n";
        for ($j = $viewQueriesBefore; $j < $viewQueriesAfter; $j++) {
            echo "  " . ($j + 1) . ". " . $GLOBALS['queries'][$j]['sql'] . "\n";
        }
    }
} catch (\Throwable $e) {
    echo "Blade render error (likely auth/session): " . $e->getMessage() . "\n";
}
