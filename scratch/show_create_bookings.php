<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$res = Illuminate\Support\Facades\DB::select('SHOW CREATE TABLE bookings');
echo $res[0]->{'Create Table'} . "\n";
