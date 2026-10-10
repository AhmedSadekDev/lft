<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\BookingContainer;

$c = new BookingContainer([
    'status' => 1,
    'superagent_specification_approved' => 1,
    'is_in_loading' => 1,
    'container_no' => 'TEST',
]);

echo "Attributes in model:\n";
print_r($c->getAttributes());
