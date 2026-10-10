<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Running Parallel test first...\n";
$p = new Tests\Feature\ParallelContainerStagesTest('test_superagent_done_actions_advance_all_stages_without_an_agent');
$p->runBare();

echo "Now running Phase6 test 10...\n";
try {
    $t10 = new Tests\Feature\Phase6MobilePaginationTest('test_superagent_pending_stage_receipts_pagination');
    $t10->runBare();
    echo "Test 10 PASSED!\n";
} catch (\Throwable $e) {
    echo "Test 10 FAILED: " . $e->getMessage() . "\n";
}

echo "Now running Phase6 test 11...\n";
try {
    $t11 = new Tests\Feature\Phase6MobilePaginationTest('test_public_tracking_fetches_booking_and_containers_with_eager_loading');
    $t11->runBare();
    echo "Test 11 PASSED!\n";
} catch (\Throwable $e) {
    echo "Test 11 FAILED: " . $e->getMessage() . "\n";
}

echo "Now running Phase6 test 18...\n";
try {
    $t18 = new Tests\Feature\Phase6MobilePaginationTest('test_client_portal_company_bookings_pagination');
    $t18->runBare();
    echo "Test 18 PASSED!\n";
} catch (\Throwable $e) {
    echo "Test 18 FAILED: " . $e->getMessage() . "\n";
}
