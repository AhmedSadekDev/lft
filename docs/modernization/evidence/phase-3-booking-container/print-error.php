<?php
require __DIR__.'/phase3-bootstrap.php';
$controller = $app->make(\App\Http\Controllers\Api\Superagent\BookingContainerController::class);

foreach (['specification', 'waiting'] as $st) {
    $request = \Illuminate\Http\Request::create('http://localhost/api/superagent/booking/missions/all', 'GET', ['stage_type' => $st]);
    $response = $controller->all($request);
    echo "STAGE $st: status=".$response->getStatusCode()."\n";
    echo substr($response->getContent(), 0, 200)."\n\n";
}
