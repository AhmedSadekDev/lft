<?php

namespace Tests\Feature;

use App\Jobs\SendPushNotificationJob;
use App\Models\Agent;
use App\Models\AgentPhoto;
use App\Models\Branch;
use App\Models\shippingAgent;
use App\Models\Yard;
use App\Notifications\ConatinerStatus;
use App\Services\ReferenceDataService;
use App\Services\ThumbnailService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class Phase7QueuesAndCachingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        \Mockery::close();
        auth()->forgetGuards();

        $ref = new \ReflectionProperty(\Illuminate\Database\Eloquent\Model::class, 'guardableColumns');
        $ref->setAccessible(true);
        $ref->setValue(null, []);
        \Illuminate\Database\Eloquent\Model::unguard();

        DB::disconnect('sqlite');
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'cache.default' => 'array',
        ]);
        DB::reconnect('sqlite');
        Schema::dropAllTables();
        $this->createTestTables();
    }

    protected function tearDown(): void
    {
        \Mockery::close();
        auth()->forgetGuards();

        $ref = new \ReflectionProperty(\Illuminate\Database\Eloquent\Model::class, 'guardableColumns');
        $ref->setAccessible(true);
        $ref->setValue(null, []);
        \Illuminate\Database\Eloquent\Model::reguard();

        Schema::dropAllTables();
        DB::disconnect('sqlite');
        parent::tearDown();
    }

    private function createTestTables(): void
    {
        Schema::create('yards', function (Blueprint $table) {
            $table->id();
            $table->json('title')->nullable();
            $table->timestamps();
        });

        Schema::create('shipping_agents', function (Blueprint $table) {
            $table->id();
            $table->json('title')->nullable();
            $table->json('description')->nullable();
            $table->string('image')->nullable();
            $table->timestamps();
        });

        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('factory_id')->nullable();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('agents', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->string('device_token')->nullable();
            $table->decimal('wallet', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('agent_photos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('agent_id');
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->unsignedBigInteger('booking_id')->nullable();
            $table->unsignedBigInteger('booking_container_id')->nullable();
            $table->unsignedBigInteger('booking_paper_id')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamps();
        });

        // Queue infrastructure table in isolated SQLite database for worker verification
        Schema::create('jobs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('queue')->index();
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });

        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('uuid')->unique();
            $table->text('connection');
            $table->text('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();
        });
    }

    // =========================================================================
    // SECTION 1: FCM NOTIFICATIONS & BACKGROUND QUEUE TESTS
    // =========================================================================

    public function test_fcm_job_payload_preservation_and_serialization(): void
    {
        $token = 'test_device_token_xyz_123';
        $title = 'مهمة جديدة';
        $text = 'تم إسناد حاوية رقم B-101 لك بنجاح';
        $data = ['container_id' => 101, 'stage' => 'loading'];
        $eventId = 'event_dispatch_test_001';

        $job = new SendPushNotificationJob($token, $title, $text, $data, $eventId);

        $this->assertInstanceOf(ShouldQueue::class, $job);
        $this->assertSame($token, $job->token);
        $this->assertSame($title, $job->title);
        $this->assertSame($text, $job->text);
        $this->assertSame($data, $job->data);
        $this->assertSame($eventId, $job->eventId);

        // Test serialization cycle (how queues store jobs)
        $serialized = serialize($job);
        $unserialized = unserialize($serialized);

        $this->assertSame($token, $unserialized->token);
        $this->assertSame($title, $unserialized->title);
        $this->assertSame($text, $unserialized->text);
        $this->assertSame($data, $unserialized->data);
        $this->assertSame($eventId, $unserialized->eventId);
    }

    public function test_fcm_job_retry_and_backoff_configuration(): void
    {
        $job = new SendPushNotificationJob('tok', 'title', 'msg');

        $this->assertSame(3, $job->tries);
        $this->assertSame([5, 30, 120], $job->backoff);
        $this->assertSame(30, $job->timeout);
    }

    public function test_fcm_job_skips_empty_token_permanently(): void
    {
        $job = new SendPushNotificationJob('   ', 'Title', 'Message');
        $result = $job->handle();

        $this->assertFalse($result);
    }

    public function test_fcm_job_idempotency_prevents_duplicate_dispatch(): void
    {
        $token = 'device_token_abc';
        $eventId = 'booking_stage_assigned_999';

        // Pre-mark idempotency key in cache
        $lockKey = 'push_event_processed_' . md5($eventId . '_' . $token);
        Cache::put($lockKey, true, 300);

        $job = new SendPushNotificationJob($token, 'Title', 'Msg', [], $eventId);
        $result = $job->handle();

        // Duplicate suppressed by idempotency
        $this->assertTrue($result);
    }

    public function test_real_asynchronous_worker_processing_in_isolated_environment(): void
    {
        // 1. Configure queue to use database driver on our isolated SQLite test DB
        config([
            'queue.default' => 'database',
            'queue.connections.database' => [
                'driver' => 'database',
                'table' => 'jobs',
                'queue' => 'default',
                'retry_after' => 90,
                'connection' => 'sqlite',
            ],
        ]);

        $this->assertSame(0, DB::table('jobs')->count());

        // 2. Dispatch job asynchronously
        SendPushNotificationJob::dispatch('', 'Background Test', 'Asynchronous Execution Verification');

        // 3. PROVE that job was pushed to queue table and NOT executed during HTTP/dispatch cycle
        $this->assertSame(1, DB::table('jobs')->count(), 'Job must be queued in jobs table without executing synchronously');

        $queuedRow = DB::table('jobs')->first();
        $payload = json_decode($queuedRow->payload, true);
        $this->assertStringContainsString('SendPushNotificationJob', $payload['displayName']);

        // 4. Run real queue worker to process the queued job
        Artisan::call('queue:work', [
            '--once' => true,
            'connection' => 'database',
        ]);

        // 5. PROVE that worker consumed and processed the job
        $this->assertSame(0, DB::table('jobs')->count(), 'Worker must process and remove completed job from jobs table');
    }

    public function test_container_status_notification_implements_should_queue(): void
    {
        $notification = new ConatinerStatus((object) ['id' => 1], 'تحديث الحالة');

        $this->assertInstanceOf(ShouldQueue::class, $notification, 'Container status notification must implement ShouldQueue');
    }

    // =========================================================================
    // SECTION 2: CAUTIOUS CACHING & REFERENCE DATA TESTS
    // =========================================================================

    public function test_reference_data_caching_and_cache_hit(): void
    {
        Yard::create(['title' => ['ar' => 'ساحة الإسكندرية', 'en' => 'Alexandria Yard']]);
        Yard::create(['title' => ['ar' => 'ساحة الدخيلة', 'en' => 'Dekheila Yard']]);

        // Clear any leftover cache
        ReferenceDataService::clearYardsCache();
        $this->assertFalse(Cache::has(ReferenceDataService::KEY_YARDS_ALL));

        // First call: cache miss, loads from database into cache
        $yards1 = ReferenceDataService::getYards();
        $this->assertCount(2, $yards1);
        $this->assertTrue(Cache::has(ReferenceDataService::KEY_YARDS_ALL));

        // Second call: cache hit, served directly from cache
        $yards2 = ReferenceDataService::getYards();
        $this->assertCount(2, $yards2);
    }

    public function test_yard_observer_invalidates_cache_on_create_update_and_delete(): void
    {
        $yard = Yard::create(['title' => ['ar' => 'ساحة 1']]);
        ReferenceDataService::getYards();
        ReferenceDataService::getYardsPluck();

        $this->assertTrue(Cache::has(ReferenceDataService::KEY_YARDS_ALL));
        $this->assertTrue(Cache::has(ReferenceDataService::KEY_YARDS_PLUCK));

        // Update model: Observer must automatically invalidate cache
        $yard->update(['title' => ['ar' => 'ساحة 1 المعدلة']]);

        $this->assertFalse(Cache::has(ReferenceDataService::KEY_YARDS_ALL));
        $this->assertFalse(Cache::has(ReferenceDataService::KEY_YARDS_PLUCK));

        // Re-cache
        ReferenceDataService::getYards();
        $this->assertTrue(Cache::has(ReferenceDataService::KEY_YARDS_ALL));

        // Delete model: Observer must invalidate cache
        $yard->delete();
        $this->assertFalse(Cache::has(ReferenceDataService::KEY_YARDS_ALL));
    }

    public function test_shipping_agent_and_branch_observers_invalidate_cache(): void
    {
        $agent = shippingAgent::create(['title' => ['ar' => 'ميرسك', 'en' => 'Maersk']]);
        $branch = Branch::create(['name' => 'فرع العاشر من رمضان']);

        ReferenceDataService::getShippingAgents();
        ReferenceDataService::getBranches();

        $this->assertTrue(Cache::has(ReferenceDataService::KEY_SHIPPING_AGENTS_ALL));
        $this->assertTrue(Cache::has(ReferenceDataService::KEY_BRANCHES_ALL));

        // Update shipping agent
        $agent->update(['title' => ['ar' => 'ميرسك العالمية']]);
        $this->assertFalse(Cache::has(ReferenceDataService::KEY_SHIPPING_AGENTS_ALL));

        // Update branch
        $branch->update(['name' => 'فرع السويس']);
        $this->assertFalse(Cache::has(ReferenceDataService::KEY_BRANCHES_ALL));
    }

    public function test_strict_financial_cache_prohibition_invariant(): void
    {
        // Strict invariant: verify that agent financial wallet is NOT cached
        $agent = Agent::create([
            'name' => 'مندوب مالي',
            'email' => 'agent@test.com',
            'wallet' => 1500.00,
        ]);

        $this->assertSame('1500.00', number_format((float) $agent->wallet, 2, '.', ''));

        // Modify wallet directly
        $agent->wallet = 2000.00;
        $agent->save();

        // Fresh fetch must read real DB value directly, without stale cache interference
        $fresh = Agent::find($agent->id);
        $this->assertSame('2000.00', number_format((float) $fresh->wallet, 2, '.', ''));
        $this->assertFalse(Cache::has("agent_wallet_{$agent->id}"));
    }

    // =========================================================================
    // SECTION 3: NATIVE PHP GD THUMBNAIL SERVICE TESTS
    // =========================================================================

    public function test_thumbnail_service_resizes_jpeg_preserving_aspect_ratio(): void
    {
        $service = new ThumbnailService();
        $tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'lft_tests_' . uniqid();
        @mkdir($tempDir, 0755, true);

        $sourceFile = $tempDir . DIRECTORY_SEPARATOR . 'source_600x400.jpg';

        // Create 600x400 JPEG image via GD
        $img = imagecreatetruecolor(600, 400);
        $color = imagecolorallocate($img, 100, 150, 200);
        imagefilledrectangle($img, 0, 0, 600, 400, $color);
        imagejpeg($img, $sourceFile);
        imagedestroy($img);

        $this->assertFileExists($sourceFile);

        // Generate 300x300 thumbnail (expected: 300x200 preserving 3:2 ratio)
        $thumbFile = $service->generateThumbnail($sourceFile, null, 300, 300);

        $this->assertNotNull($thumbFile);
        $this->assertFileExists($thumbFile);

        [$w, $h] = getimagesize($thumbFile);
        $this->assertSame(300, $w);
        $this->assertSame(200, $h);

        // Clean up
        @unlink($thumbFile);
        @unlink($sourceFile);
        @rmdir(dirname($thumbFile));
        @rmdir($tempDir);
    }

    public function test_thumbnail_service_handles_png_with_transparency(): void
    {
        $service = new ThumbnailService();
        $tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'lft_tests_' . uniqid();
        @mkdir($tempDir, 0755, true);

        $sourceFile = $tempDir . DIRECTORY_SEPARATOR . 'source_400x400.png';

        $img = imagecreatetruecolor(400, 400);
        imagealphablending($img, false);
        imagesavealpha($img, true);
        $trans = imagecolorallocatealpha($img, 0, 0, 0, 127);
        imagefilledrectangle($img, 0, 0, 400, 400, $trans);
        imagepng($img, $sourceFile);
        imagedestroy($img);

        $thumbFile = $service->generateThumbnail($sourceFile, null, 200, 200);

        $this->assertNotNull($thumbFile);
        $this->assertFileExists($thumbFile);

        [$w, $h] = getimagesize($thumbFile);
        $this->assertSame(200, $w);
        $this->assertSame(200, $h);

        @unlink($thumbFile);
        @unlink($sourceFile);
        @rmdir(dirname($thumbFile));
        @rmdir($tempDir);
    }

    public function test_thumbnail_service_rejects_path_traversal_and_remote_urls(): void
    {
        $service = new ThumbnailService();

        $this->assertNull($service->generateThumbnail('../../etc/passwd'));
        $this->assertNull($service->generateThumbnail('http://example.com/malicious.jpg'));
        $this->assertNull($service->generateThumbnail("foo\0bar.jpg"));
    }

    public function test_thumbnail_service_never_overwrites_original_image(): void
    {
        $service = new ThumbnailService();
        $tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'lft_tests_' . uniqid();
        @mkdir($tempDir, 0755, true);

        $sourceFile = $tempDir . DIRECTORY_SEPARATOR . 'original.jpg';
        $img = imagecreatetruecolor(200, 200);
        imagejpeg($img, $sourceFile);
        imagedestroy($img);

        // Destination is identical to source
        $result = $service->generateThumbnail($sourceFile, $sourceFile);

        $this->assertNull($result, 'ThumbnailService must refuse to overwrite original file');

        @unlink($sourceFile);
        @rmdir($tempDir);
    }

    public function test_thumbnail_service_fallback_to_original_url(): void
    {
        $service = new ThumbnailService();

        $original = 'https://cloudymenue.cloudy-digital.com/storage/uploads/document.pdf';
        $thumb = $service->getThumbnailUrl($original);

        $this->assertSame($original, $thumb, 'Non-image or remote files must gracefully fallback to original URL');
    }

    public function test_agent_photos_listing_includes_thumbnail_route(): void
    {
        $agent = Agent::create(['name' => 'مندوب اختبار', 'email' => 'ag@test.com']);
        $this->actingAs($agent, 'agent');

        Storage::fake('agent_photos');
        Storage::disk('agent_photos')->put('test.jpg', 'fake-image-bytes');

        AgentPhoto::create([
            'agent_id' => $agent->id,
            'path' => 'test.jpg',
            'original_name' => 'test.jpg',
        ]);

        $response = $this->getJson('/api/agent/photos');

        $response->assertOk()
            ->assertJsonStructure([
                'status',
                'data' => [
                    'data' => [
                        '*' => [
                            'id',
                            'image',
                            'thumbnail',
                            'original_name',
                            'created_at',
                        ],
                    ],
                ],
                'pagination',
            ]);

        $this->assertStringContainsString('thumb=1', $response->json('data.data.0.thumbnail'));
    }
}
