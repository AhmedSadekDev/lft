<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    // 1. Run AgentPhotosTest test 12
    $t12 = new Tests\Feature\AgentPhotosTest('test_assign_photo_copies_to_public_storage_and_creates_booking_paper');
    $t12->runBare();
    echo "T12 completed.\n";
} catch (\Throwable $e) {
    echo "T12 error: " . $e->getMessage() . "\n";
}

try {
    config([
        'database.connections.sqlite_phase6' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => false,
        ],
        'database.default' => 'sqlite_phase6',
    ]);
    $p6 = new Tests\Feature\Phase6MobilePaginationTest('test_superagent_pending_stage_receipts_pagination');
    $p6->runBare();
    echo "P6 Test 10 PASSED!\n";
} catch (\Throwable $e) {
    echo "P6 error: " . $e->getMessage() . "\n";
}
