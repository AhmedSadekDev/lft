<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Run test 12 of AgentPhotosTest
$runner = new class extends Tests\Feature\AgentPhotosTest {
    public function __construct() { parent::__construct('test_assign_photo_copies_to_public_storage_and_creates_booking_paper'); }
    public function runTest() {
        $this->setUp();
        $this->test_assign_photo_copies_to_public_storage_and_creates_booking_paper();
        $this->tearDown();
    }
};
$runner->runTest();

// Now run test 10 of Phase6
$p6 = new class extends Tests\Feature\Phase6MobilePaginationTest {
    public function __construct() { parent::__construct('test_superagent_pending_stage_receipts_pagination'); }
    public function runTest() {
        $this->setUp();
        \Illuminate\Support\Facades\DB::enableQueryLog();
        $this->test_superagent_pending_stage_receipts_pagination();
        $this->tearDown();
    }
};

try {
    $p6->runTest();
    echo "SUCCESS!\n";
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    print_r(\Illuminate\Support\Facades\DB::getQueryLog());
}
