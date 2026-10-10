<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$test = new Tests\Feature\Phase6MobilePaginationTest('test_superagent_pending_stage_receipts_pagination');
$test->setRunTestInSeparateProcess(false);
$test->runBare();
echo "PASS!\n";
