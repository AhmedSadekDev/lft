<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Agent;
use App\Models\Superagent;
use App\Models\Company;
use Illuminate\Http\Request;

// 1. Agent Notification
$agent = Agent::first() ?: Agent::create(['name' => 'Test Agent', 'phone' => '1234567890']);
auth()->guard('agent')->setUser($agent);

echo "=== 1. Agent Notifications ===\n";
$req = Request::create('/api/agent/fetch_your_notifications', 'POST', ['page' => 1, 'per_page' => 2]);
$res = app()->handle($req);
$json = json_decode($res->getContent(), true);
echo json_encode([
    'status' => $json['status'] ?? null,
    'message' => $json['message'] ?? null,
    'pagination' => $json['pagination'] ?? 'MISSING',
    'data_type' => gettype($json['data'] ?? null),
    'data_count' => is_array($json['data'] ?? null) ? count($json['data']) : null,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";

// 2. Superagent Missions (e.g. Specification)
$super = Superagent::first() ?: Superagent::create(['name' => 'Test Super', 'phone' => '9876543210']);
auth()->guard('superagent')->setUser($super);

echo "\n=== 2. Superagent Missions (Specification) ===\n";
$req = Request::create('/api/superagent/booking/specification', 'GET', ['page' => 1, 'per_page' => 2]);
$res = app()->handle($req);
$json = json_decode($res->getContent(), true);
echo json_encode([
    'status' => $json['status'] ?? null,
    'message' => $json['message'] ?? null,
    'pagination' => $json['pagination'] ?? 'MISSING',
    'data_type' => gettype($json['data'] ?? null),
    'has_nested_data' => isset($json['data']['data']),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";

// 3. Desktop Orders
echo "\n=== 3. Desktop Orders ===\n";
$req = Request::create('/api/desktop/orders/all', 'GET', ['page' => 1, 'per_page' => 2]);
$res = app()->handle($req);
$json = json_decode($res->getContent(), true);
echo json_encode([
    'status' => $json['status'] ?? null,
    'pagination' => $json['pagination'] ?? 'MISSING',
    'data_count' => is_array($json['data'] ?? null) ? count($json['data']) : null,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";

// 4. Client Portal Bookings
echo "\n=== 4. Client Portal Company Bookings ===\n";
$company = Company::first();
if ($company) {
    auth()->guard('api')->setUser($company);
}
$req = Request::create('/api/profile/bookings', 'GET', ['page' => 1, 'per_page' => 2]);
$res = app()->handle($req);
$json = json_decode($res->getContent(), true);
echo json_encode([
    'status' => $json['status'] ?? null,
    'pagination' => $json['pagination'] ?? 'MISSING',
    'data_count' => is_array($json['data'] ?? null) ? count($json['data']) : null,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
