<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    $apt = new Tests\Feature\AgentPhotosTest('test_assign_photo_copies_to_public_storage_and_creates_booking_paper');
    $apt->runBare();
    echo "AgentPhotosTest passed.\n";
} catch (\Throwable $e) {
    echo "AgentPhotosTest error: " . $e->getMessage() . "\n";
}

try {
    $p6 = new Tests\Feature\Phase6MobilePaginationTest('test_superagent_pending_stage_receipts_pagination');
    $p6->runBare();
    echo "Phase6 test 10 passed.\n";
} catch (\Throwable $e) {
    echo "Phase6 test 10 FAILED: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
