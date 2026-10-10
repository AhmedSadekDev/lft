<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

class ParallelRunner extends Tests\Feature\ParallelContainerStagesTest {
    public function doSetup() { $this->setUp(); }
}
class Phase6Runner extends Tests\Feature\Phase6MobilePaginationTest {
    public function doSetup() { $this->setUp(); }
}

$test1 = new ParallelRunner('test_superagent_done_actions_advance_all_stages_without_an_agent');
$test1->doSetup();

echo "After ParallelContainerStagesTest setUp:\n";
echo "Tables in sqlite: " . implode(', ', Illuminate\Support\Facades\DB::connection('sqlite')->getDoctrineSchemaManager()->listTableNames()) . "\n";
echo "Containers columns: " . implode(', ', array_keys(Illuminate\Support\Facades\DB::connection('sqlite')->getDoctrineSchemaManager()->listTableColumns('containers'))) . "\n";

$test2 = new Phase6Runner('test_public_tracking_fetches_booking_and_containers_with_eager_loading');
$test2->doSetup();

echo "\nAfter Phase6MobilePaginationTest setUp:\n";
echo "Containers columns: " . implode(', ', array_keys(Illuminate\Support\Facades\DB::connection('sqlite')->getDoctrineSchemaManager()->listTableColumns('containers'))) . "\n";
echo "Auth employees check: " . (auth('employees')->check() ? 'TRUE' : 'FALSE') . "\n";
echo "Auth api check: " . (auth('api')->check() ? 'TRUE' : 'FALSE') . "\n";
