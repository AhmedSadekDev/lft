<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Http\Controllers\Api\Desktop\Orders\OrderController;
use Illuminate\Http\Request;

$c = app(OrderController::class);
try {
    $req = Request::create('/api/desktop/orders/all', 'GET', ['limit' => 2]);
    $res = $c->all($req);
    echo "STATUS: " . $res->getStatusCode() . "\n";
    $json = json_decode($res->getContent(), true);
    echo "JSON OUTPUT:\n";
    echo json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine() . "\n";
}
