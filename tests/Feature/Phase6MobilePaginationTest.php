<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\AgentPhoto;
use App\Models\AppNotification;
use App\Models\Booking;
use App\Models\BookingContainer;
use App\Models\Company;
use App\Models\DeliveryPolicy;
use App\Models\Driver;
use App\Models\Car;
use App\Models\MoneyTransfer;
use App\Models\AgentExpense;
use App\Models\Superagent;
use App\Models\Yard;
use App\Models\ShippingAgent;
use App\Models\Container;
use App\Models\BookingPaper;
use App\Models\Image;
use App\Models\Invoice;
use App\Models\Factory;
use App\Models\Employee;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class Phase6MobilePaginationTest extends TestCase
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
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::reconnect('sqlite');
        Schema::dropAllTables();

        $this->createInMemorySchema();
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

    private function createInMemorySchema(): void
    {

        Schema::create('agents', function (Blueprint $t) {
            $t->increments('id');
            $t->string('name')->default('Agent');
            $t->string('email')->nullable();
            $t->string('phone')->nullable();
            $t->decimal('wallet', 15, 2)->default(0);
            $t->decimal('total_wallet', 15, 2)->default(0);
            $t->decimal('spented_financial_custody', 15, 2)->default(0);
            $t->string('device_token')->nullable();
            $t->timestamps();
        });

        Schema::create('superagents', function (Blueprint $t) {
            $t->increments('id');
            $t->string('name')->default('Superagent');
            $t->string('email')->nullable();
            $t->decimal('wallet', 15, 2)->default(0);
            $t->string('device_token')->nullable();
            $t->timestamps();
        });

        Schema::create('app_notifications', function (Blueprint $t) {
            $t->increments('id');
            $t->string('title')->nullable();
            $t->text('text')->nullable();
            $t->unsignedInteger('notificationable_id')->nullable();
            $t->string('notificationable_type')->nullable();
            $t->unsignedInteger('booking_container_id')->nullable();
            $t->integer('type')->default(0);
            $t->boolean('is_read')->default(false);
            $t->timestamps();
        });

        Schema::create('companies', function (Blueprint $t) {
            $t->increments('id');
            $t->string('name')->default('Company');
            $t->string('email')->nullable();
            $t->string('password')->nullable();
            $t->boolean('taxed')->default(0);
            $t->timestamps();
        });

        Schema::create('factories', function (Blueprint $t) {
            $t->increments('id');
            $t->string('name')->default('Factory');
            $t->timestamps();
        });

        Schema::create('yards', function (Blueprint $t) {
            $t->increments('id');
            $t->string('title')->default('Yard');
            $t->timestamps();
        });

        Schema::create('shipping_agents', function (Blueprint $t) {
            $t->increments('id');
            $t->string('title')->default('Shipping Agent');
            $t->timestamps();
        });

        Schema::create('cars', function (Blueprint $t) {
            $t->increments('id');
            $t->string('car_number')->default('123');
            $t->timestamps();
        });

        Schema::create('drivers', function (Blueprint $t) {
            $t->increments('id');
            $t->string('name')->default('Driver');
            $t->timestamps();
        });

        Schema::create('services', function (Blueprint $t) {
            $t->increments('id');
            $t->string('name')->default('Service');
            $t->timestamps();
        });

        Schema::create('containers', function (Blueprint $t) {
            $t->increments('id');
            $t->string('type')->default('40ft');
            $t->string('size')->default('40');
            $t->timestamps();
        });

        Schema::create('employees', function (Blueprint $t) {
            $t->increments('id');
            $t->string('name')->default('Employee');
            $t->unsignedInteger('company_id')->nullable();
            $t->timestamps();
        });

        Schema::create('bookings', function (Blueprint $t) {
            $t->increments('id');
            $t->string('booking_number')->default('BK-001');
            $t->unsignedInteger('company_id')->nullable();
            $t->unsignedInteger('yard_id')->nullable();
            $t->unsignedInteger('shipping_agent_id')->nullable();
            $t->unsignedInteger('factory_id')->nullable();
            $t->unsignedInteger('employee_id')->nullable();
            $t->string('employee_name')->nullable();
            $t->string('certificate_number')->nullable();
            $t->string('type_of_action')->nullable();
            $t->date('discharge_date')->nullable();
            $t->date('permit_end_date')->nullable();
            $t->string('submission_id')->nullable();
            $t->string('invoice_uuid')->nullable();
            $t->integer('is_submitted')->default(0);
            $t->string('invoice_status')->nullable();
            $t->string('signature_company')->nullable();
            $t->integer('signature_company_id')->nullable();
            $t->timestamp('signature_date')->nullable();
            $t->timestamps();
        });

        Schema::create('booking_containers', function (Blueprint $t) {
            $t->increments('id');
            $t->unsignedInteger('booking_id');
            $t->string('container_no')->default('CONT-001');
            $t->integer('status')->default(0);
            $t->boolean('superagent_specification_approved')->default(0);
            $t->boolean('superagent_loading_approved')->default(0);
            $t->boolean('superagent_unloading_approved')->default(0);
            $t->boolean('is_in_loading')->default(0);
            $t->timestamp('specification_completed_at')->nullable();
            $t->timestamp('loading_completed_at')->nullable();
            $t->timestamp('unloading_completed_at')->nullable();
            $t->timestamp('arrival_date')->nullable();
            $t->unsignedInteger('container_id')->nullable();
            $t->unsignedInteger('branch_id')->nullable();
            $t->timestamps();
        });

        Schema::create('booking_papers', function (Blueprint $t) {
            $t->increments('id');
            $t->unsignedInteger('booking_id')->nullable();
            $t->unsignedInteger('booking_container_id')->nullable();
            $t->integer('type')->default(0);
            $t->timestamps();
        });

        Schema::create('notes', function (Blueprint $t) {
            $t->increments('id');
            $t->string('attached_type')->nullable();
            $t->unsignedInteger('attached_id')->nullable();
            $t->text('notes')->nullable();
            $t->timestamps();
        });

        Schema::create('invoices', function (Blueprint $t) {
            $t->increments('id');
            $t->unsignedInteger('booking_id');
            $t->string('invoice_number')->nullable();
            $t->timestamps();
        });

        Schema::create('delivery_policies', function (Blueprint $t) {
            $t->increments('id');
            $t->unsignedInteger('car_id')->nullable();
            $t->unsignedInteger('driver_id')->nullable();
            $t->date('date')->nullable();
            $t->string('address')->nullable();
            $t->decimal('office_commission', 10, 2)->default(0);
            $t->boolean('is_settled')->default(0);
            $t->timestamps();
        });

        Schema::create('delivery_policy_containers', function (Blueprint $t) {
            $t->increments('id');
            $t->unsignedInteger('delivery_policy_id');
            $t->unsignedInteger('booking_container_id');
            $t->timestamps();
        });

        Schema::create('money_transfers', function (Blueprint $t) {
            $t->increments('id');
            $t->decimal('value', 12, 2)->default(0);
            $t->integer('type')->default(1);
            $t->string('transferer_type')->nullable();
            $t->unsignedInteger('transferer_id')->nullable();
            $t->string('transfered_type')->nullable();
            $t->unsignedInteger('transfered_id')->nullable();
            $t->unsignedInteger('delivery_policy_id')->nullable();
            $t->date('date')->nullable();
            $t->string('address')->nullable();
            $t->timestamps();
        });

        Schema::create('agent_expenses', function (Blueprint $t) {
            $t->increments('id');
            $t->unsignedInteger('agent_id');
            $t->unsignedInteger('booking_id')->nullable();
            $t->unsignedInteger('booking_container_id')->nullable();
            $t->unsignedInteger('booking_service_id')->nullable();
            $t->unsignedInteger('delivery_policy_id')->nullable();
            $t->integer('type')->default(1);
            $t->integer('type_id')->nullable();
            $t->decimal('value', 10, 2)->default(0);
            $t->integer('version')->default(1);
            $t->boolean('is_hidden')->default(false);
            $t->integer('status')->default(0);
            $t->text('notes')->nullable();
            $t->timestamp('voided_at')->nullable();
            $t->timestamps();
        });

        Schema::create('agent_photos', function (Blueprint $t) {
            $t->increments('id');
            $t->unsignedInteger('agent_id');
            $t->string('path')->default('photos/test.jpg');
            $t->string('original_name')->default('test.jpg');
            $t->timestamps();
        });

        Schema::create('booking_container_agents', function (Blueprint $t) {
            $t->increments('id');
            $t->unsignedInteger('booking_container_id');
            $t->unsignedInteger('agent_id');
            $t->integer('stage_type')->default(0);
            $t->integer('booking_container_status')->default(0);
            $t->boolean('superagent_specification_approved')->default(0);
            $t->boolean('superagent_loading_approved')->default(0);
            $t->boolean('superagent_unloading_approved')->default(0);
            $t->boolean('is_in_loading')->default(0);
            $t->timestamp('specification_completed_at')->nullable();
            $t->timestamp('specification_approved_at')->nullable();
            $t->timestamp('loading_completed_at')->nullable();
            $t->timestamp('loading_approved_at')->nullable();
            $t->timestamp('unloading_completed_at')->nullable();
            $t->timestamp('unloading_approved_at')->nullable();
            $t->timestamps();
        });

        Schema::create('booking_container_superagents', function (Blueprint $t) {
            $t->increments('id');
            $t->unsignedInteger('booking_container_id');
            $t->unsignedInteger('superagent_id');
            $t->timestamps();
        });

        Schema::create('container_stages', function (Blueprint $t) {
            $t->increments('id');
            $t->unsignedInteger('booking_container_id');
            $t->integer('type_id');
            $t->integer('version')->default(1);
            $t->timestamp('receipts_closed_at')->nullable();
            $t->unsignedInteger('receipts_closed_by')->nullable();
            $t->timestamps();
        });

        Schema::create('booking_container_stages', function (Blueprint $t) {
            $t->increments('id');
            $t->unsignedInteger('booking_container_id');
            $t->integer('type_id');
            $t->integer('version')->default(1);
            $t->timestamp('receipts_closed_at')->nullable();
            $t->unsignedInteger('receipts_closed_by')->nullable();
            $t->timestamps();
        });

        Schema::create('images', function (Blueprint $t) {
            $t->increments('id');
            $t->string('image')->default('test.jpg');
            $t->unsignedInteger('imageable_id')->nullable();
            $t->string('imageable_type')->nullable();
            $t->timestamps();
        });

        Schema::create('branches', function (Blueprint $t) {
            $t->increments('id');
            $t->string('name')->default('Branch');
            $t->timestamps();
        });

        Schema::create('daily_booking_containers', function (Blueprint $t) {
            $t->increments('id');
            $t->unsignedInteger('booking_container_id');
            $t->unsignedInteger('superagent_id')->nullable();
            $t->integer('booking_container_status')->default(0);
            $t->timestamps();
        });

        Schema::create('booking_movements', function (Blueprint $t) {
            $t->increments('id');
            $t->unsignedInteger('booking_id')->nullable();
            $t->unsignedInteger('container_id')->nullable();
            $t->string('status')->nullable();
            $t->date('date')->nullable();
            $t->timestamps();
        });
    }

    public function test_agent_notifications_pagination_contract(): void
    {
        $agent = Agent::create(['name' => 'Agent 1']);
        $otherAgent = Agent::create(['name' => 'Agent 2']);

        for ($i = 1; $i <= 25; $i++) {
            AppNotification::create([
                'title' => "Notification $i",
                'notificationable_id' => $agent->id,
                'notificationable_type' => Agent::class,
                'type' => 1,
            ]);
        }
        for ($i = 1; $i <= 5; $i++) {
            AppNotification::create([
                'title' => "Other Notif $i",
                'notificationable_id' => $otherAgent->id,
                'notificationable_type' => Agent::class,
                'type' => 1,
            ]);
        }

        $this->actingAs($agent, 'agent');

        $response = $this->postJson('/api/agent/fetch_your_notifications');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'errNum',
            'message',
            'data',
            'pagination' => ['total', 'per_page', 'current_page', 'total_pages'],
        ]);

        $json = $response->json();
        $this->assertEquals(25, $json['pagination']['total']);
        $this->assertEquals(20, $json['pagination']['per_page']);
        $this->assertEquals(1, $json['pagination']['current_page']);
        $this->assertEquals(2, $json['pagination']['total_pages']);
        $this->assertCount(20, $json['data']);

        $response2 = $this->postJson('/api/agent/fetch_your_notifications?page=2&per_page=10');
        $response2->assertStatus(200);
        $json2 = $response2->json();
        $this->assertEquals(25, $json2['pagination']['total']);
        $this->assertEquals(10, $json2['pagination']['per_page']);
        $this->assertEquals(2, $json2['pagination']['current_page']);
        $this->assertEquals(3, $json2['pagination']['total_pages']);
        $this->assertCount(10, $json2['data']);

        $responseOut = $this->postJson('/api/agent/fetch_your_notifications?page=99&per_page=10');
        $responseOut->assertStatus(200);
        $this->assertCount(0, $responseOut->json('data'));
    }

    public function test_superagent_notifications_pagination_and_scoping(): void
    {
        $superagent = Superagent::create(['name' => 'Super 1']);
        for ($i = 1; $i <= 35; $i++) {
            AppNotification::create([
                'title' => "Super Notif $i",
                'notificationable_id' => $superagent->id,
                'notificationable_type' => Superagent::class,
                'type' => 1,
            ]);
        }

        $this->actingAs($superagent, 'superagent');

        $response = $this->postJson('/api/superagent/fetch_your_notifications');
        $response->assertStatus(200);
        $json = $response->json();

        $this->assertEquals(35, $json['pagination']['total']);
        $this->assertEquals(20, $json['pagination']['per_page']);
        $this->assertEquals(1, $json['pagination']['current_page']);
        $this->assertEquals(2, $json['pagination']['total_pages']);
        $this->assertCount(20, $json['data']);
    }

    public function test_agent_delivery_policies_pagination(): void
    {
        $agent = Agent::create(['name' => 'Policy Agent']);
        $car = Car::create(['car_number' => 'ABC-123']);
        $driver = Driver::create(['name' => 'Driver 1']);

        for ($i = 1; $i <= 25; $i++) {
            $policy = DeliveryPolicy::create([
                'car_id' => $car->id,
                'driver_id' => $driver->id,
                'date' => now(),
            ]);
            MoneyTransfer::create([
                'delivery_policy_id' => $policy->id,
                'transferer_type' => Agent::class,
                'transferer_id' => $agent->id,
                'value' => 500,
                'type' => 3,
            ]);
        }

        $this->actingAs($agent, 'agent');

        $response = $this->getJson('/api/agent/fetch_delivery_policies');
        $response->assertStatus(200);
        $json = $response->json();

        $this->assertEquals(25, $json['pagination']['total']);
        $this->assertEquals(20, $json['pagination']['per_page']);
        $this->assertEquals(1, $json['pagination']['current_page']);
        $this->assertEquals(2, $json['pagination']['total_pages']);
        $this->assertCount(20, $json['data']);
    }

    public function test_agent_all_expenses_pagination(): void
    {
        $agent = Agent::create(['name' => 'Expense Agent']);

        for ($i = 1; $i <= 30; $i++) {
            AgentExpense::create([
                'agent_id' => $agent->id,
                'type' => 2,
                'value' => 100 * $i,
                'version' => 1,
                'is_hidden' => false,
                'created_at' => now(),
            ]);
        }

        $this->actingAs($agent, 'agent');

        $response = $this->postJson('/api/agent/fetch_all_expenses?type=2');
        $response->assertStatus(200);
        $json = $response->json();

        $this->assertEquals(30, $json['pagination']['total']);
        $this->assertEquals(20, $json['pagination']['per_page']);
        $this->assertEquals(1, $json['pagination']['current_page']);
        $this->assertEquals(2, $json['pagination']['total_pages']);
        $this->assertCount(20, $json['data']);
    }

    public function test_superagent_fetch_agents_pagination(): void
    {
        $superagent = Superagent::create(['name' => 'Super Agent 1']);

        for ($i = 1; $i <= 25; $i++) {
            Agent::create(['name' => "Agent Sub $i"]);
        }

        $this->actingAs($superagent, 'superagent');

        $response = $this->postJson('/api/superagent/booking/fetch_agents');
        $response->assertStatus(200);
        $json = $response->json();

        $this->assertEquals(25, $json['pagination']['total']);
        $this->assertEquals(20, $json['pagination']['per_page']);
        $this->assertEquals(1, $json['pagination']['current_page']);
        $this->assertEquals(2, $json['pagination']['total_pages']);
        $this->assertCount(20, $json['data']);
    }

    public function test_agent_photos_pagination_metadata(): void
    {
        $agent = Agent::create(['name' => 'Photo Agent']);

        for ($i = 1; $i <= 50; $i++) {
            AgentPhoto::create([
                'agent_id' => $agent->id,
                'path' => "photos/photo_$i.jpg",
                'original_name' => "photo_$i.jpg",
            ]);
        }

        $this->actingAs($agent, 'agent');

        $response = $this->getJson('/api/agent/photos?per_page=24');
        $response->assertStatus(200);
        $json = $response->json();

        $this->assertEquals(50, $json['pagination']['total']);
        $this->assertEquals(24, $json['pagination']['per_page']);
        $this->assertEquals(1, $json['pagination']['current_page']);
        $this->assertEquals(3, $json['pagination']['total_pages']);
        $this->assertCount(24, $json['data']['data'] ?? $json['data']);
    }

    public function test_agent_transfer_agents_pagination(): void
    {
        $agent = Agent::create(['name' => 'Current Agent']);
        for ($i = 1; $i <= 25; $i++) {
            Agent::create(['name' => "Other Agent $i"]);
        }

        $this->actingAs($agent, 'agent');

        $response = $this->postJson('/api/agent/fetch_agents');
        $response->assertStatus(200);
        $json = $response->json();

        $this->assertEquals(25, $json['pagination']['total']);
        $this->assertEquals(20, $json['pagination']['per_page']);
        $this->assertEquals(1, $json['pagination']['current_page']);
        $this->assertEquals(2, $json['pagination']['total_pages']);
        $this->assertCount(20, $json['data']);
    }

    public function test_agent_fetch_bookings_search_and_pagination(): void
    {
        $agent = Agent::create(['name' => 'Booking Search Agent']);
        $company = Company::create(['name' => 'Test Company']);

        for ($i = 1; $i <= 25; $i++) {
            $booking = Booking::create([
                'booking_number' => "BK-SEARCH-$i",
                'company_id' => $company->id,
            ]);
            $container = BookingContainer::create([
                'booking_id' => $booking->id,
                'container_no' => "CONT-SEARCH-$i",
                'status' => 0,
            ]);
            $agent->agent_booking_containers()->attach($container->id, [
                'stage_type' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->actingAs($agent, 'agent');

        $response = $this->postJson('/api/agent/booking/fetch_bookings');
        $response->assertStatus(200);
        $json = $response->json();

        $this->assertEquals(25, $json['pagination']['total']);
        $this->assertEquals(20, $json['pagination']['per_page']);
        $this->assertEquals(1, $json['pagination']['current_page']);
        $this->assertEquals(2, $json['pagination']['total_pages']);
        $this->assertCount(20, $json['data']);

        // Search filter
        $searchResponse = $this->postJson('/api/agent/booking/fetch_bookings', ['word' => 'BK-SEARCH-1']);
        $searchResponse->assertStatus(200);
        $searchJson = $searchResponse->json();
        $this->assertGreaterThanOrEqual(1, $searchJson['pagination']['total']);
    }

    public function test_superagent_stage_missions_pagination(): void
    {
        $superagent = Superagent::create(['name' => 'Super Missions']);
        $company = Company::create(['name' => 'Mission Company']);

        for ($i = 1; $i <= 15; $i++) {
            $booking = Booking::create([
                'booking_number' => "BK-SPEC-$i",
                'company_id' => $company->id,
            ]);
            BookingContainer::create([
                'booking_id' => $booking->id,
                'container_no' => "CONT-SPEC-$i",
                'status' => 0,
                'superagent_specification_approved' => 0,
            ]);
        }

        $this->actingAs($superagent, 'superagent');

        $response = $this->getJson('/api/superagent/booking/specification?per_page=10');
        if ($response->status() !== 200) {
            dump($response->json());
        }
        $response->assertStatus(200);
        $json = $response->json();

        $this->assertEquals(15, $json['pagination']['total']);
        $this->assertEquals(10, $json['pagination']['per_page']);
        $this->assertEquals(1, $json['pagination']['current_page']);
        $this->assertEquals(2, $json['pagination']['total_pages']);
        $this->assertCount(10, $json['data']['data']);
    }

    public function test_superagent_pending_stage_receipts_pagination(): void
    {
        $superagent = Superagent::create(['name' => 'Super Receipts']);
        $agent = Agent::create(['name' => 'Receipts Agent']);

        for ($i = 1; $i <= 15; $i++) {
            $booking = Booking::create(['booking_number' => "BK-REC-$i"]);
            $container = BookingContainer::create([
                'booking_id' => $booking->id,
                'container_no' => "CONT-REC-$i",
                'status' => 1,
                'superagent_specification_approved' => 1,
                'is_in_loading' => 1,
            ]);
            $container->agents()->attach($agent->id, [
                'stage_type' => 1,
                'is_in_loading' => 1,
            ]);
        }

        $this->actingAs($superagent, 'superagent');

        $response = $this->getJson('/api/superagent/booking/pending_stage_receipts?type_id=1');
        $response->assertStatus(200);
        $json = $response->json();

        $this->assertEquals(15, $json['pagination']['total']);
        $this->assertEquals(50, $json['pagination']['per_page']);
        $this->assertEquals(1, $json['pagination']['current_page']);
        $this->assertEquals(1, $json['pagination']['total_pages']);
        $this->assertCount(15, $json['data']['data']);
    }

    public function test_public_tracking_fetches_booking_and_containers_with_eager_loading(): void
    {
        $booking = Booking::create([
            'booking_number' => 'BK-TRACK-999',
            'discharge_date' => now()->toDateString(),
        ]);

        $containerType = Container::create([
            'type' => '40ft Dry',
            'size' => '40',
        ]);

        for ($i = 1; $i <= 3; $i++) {
            BookingContainer::create([
                'booking_id' => $booking->id,
                'container_no' => "CONT-TRK-$i",
                'container_id' => $containerType->id,
                'status' => 1,
            ]);
        }

        $response = $this->getJson('/api/booking/track?order_number=BK-TRACK-999');
        $response->assertStatus(200);
        $response->assertJson([
            'status' => true,
            'message' => 'Orders',
        ]);

        $data = $response->json('data');
        $this->assertCount(3, $data);
        $this->assertEquals('CONT-TRK-1', $data[0]['container_number']);
        $this->assertEquals('40', $data[0]['container_size']);
        $this->assertEquals('40ft Dry', $data[0]['container_type']);
    }

    public function test_public_tracking_not_found_returns_error_response(): void
    {
        $response = $this->getJson('/api/booking/track?order_number=NON-EXISTENT-BOOKING');
        $response->assertStatus(200);
        $this->assertFalse($response->json('status'));
        $this->assertEquals(__('admin.not_found'), $response->json('message'));
    }

    public function test_public_tracking_route_has_throttle_middleware(): void
    {
        $route = collect(\Illuminate\Support\Facades\Route::getRoutes()->get('GET'))->first(function ($r) {
            return $r->uri() === 'api/booking/track';
        });

        $this->assertNotNull($route);
        $middleware = $route->gatherMiddleware();
        $this->assertTrue(
            collect($middleware)->contains(fn ($m) => str_contains($m, 'throttle')),
            'Public tracking route must be protected by throttle middleware.'
        );
    }

    public function test_container_details_returns_structured_booking_info(): void
    {
        $company = Company::create(['name' => 'Details Company']);
        $booking = Booking::create([
            'booking_number' => 'BK-DETAILS-1',
            'company_id' => $company->id,
        ]);
        $containerType = Container::create(['type' => '20ft', 'size' => '20']);
        $container = BookingContainer::create([
            'booking_id' => $booking->id,
            'container_no' => 'CONT-DET-1',
            'container_id' => $containerType->id,
        ]);

        $response = $this->getJson("/api/booking/container/{$container->id}");
        $response->assertStatus(200);
        $json = $response->json();
        $this->assertTrue($json['status']);
        $this->assertArrayHasKey('bookingDetails', $json['data']);
        $this->assertArrayHasKey('factoryDetails', $json['data']);
        $this->assertArrayHasKey('lastMovements', $json['data']);
    }

    public function test_booking_papers_returns_metadata_and_eager_loaded_images(): void
    {
        $booking = Booking::create(['booking_number' => 'BK-PAPERS-1']);
        for ($i = 1; $i <= 2; $i++) {
            $paper = BookingPaper::create([
                'booking_id' => $booking->id,
                'type' => $i,
            ]);
            Image::create([
                'imageable_type' => BookingPaper::class,
                'imageable_id' => $paper->id,
                'image' => "papers/paper_$i.jpg",
            ]);
        }

        $response = $this->getJson('/api/booking/booking_papers?booking_number=BK-PAPERS-1');
        $response->assertStatus(200);
        $json = $response->json();
        $this->assertTrue($json['status']);
        $this->assertCount(2, $json['data']);
        $this->assertStringContainsString('papers/paper_1.jpg', $json['data'][0]['image']);
    }

    public function test_booking_papers_handles_non_existent_booking_gracefully(): void
    {
        $response = $this->getJson('/api/booking/booking_papers?booking_number=UNKNOWN-BOOKING');
        $response->assertStatus(200);
        $this->assertFalse($response->json('status'));
        $this->assertEquals('404', $response->json('errNum'));
    }

    public function test_desktop_orders_pagination_and_eager_loading(): void
    {
        $company = Company::create(['name' => 'Desktop Co']);
        $factory = Factory::create(['name' => 'Desktop Factory']);

        for ($i = 1; $i <= 25; $i++) {
            $booking = Booking::create([
                'booking_number' => "BK-DESK-$i",
                'company_id' => $company->id,
                'factory_id' => $factory->id,
                'is_submitted' => 0,
            ]);
            Invoice::create([
                'booking_id' => $booking->id,
                'invoice_number' => "INV-DESK-$i",
            ]);
        }

        $request = \Illuminate\Http\Request::create('/api/desktop/orders/all', 'GET', [
            'limit' => 10,
            'page' => 1,
        ]);

        $controller = app(\App\Http\Controllers\Api\Desktop\Orders\OrderController::class);
        $response = $controller->all($request);

        $json = $response->getData(true);
        $this->assertTrue($json['status']);
        $this->assertEquals(25, $json['data']['pagination']['total']);
        $this->assertEquals(10, $json['data']['pagination']['per_page']);
        $this->assertEquals(1, $json['data']['pagination']['current_page']);
        $this->assertEquals(3, $json['data']['pagination']['total_pages']);
        $this->assertCount(10, $json['data']['orders']);
    }

    public function test_client_portal_company_bookings_pagination(): void
    {
        $company = Company::create(['name' => 'Portal Company']);
        for ($i = 1; $i <= 25; $i++) {
            Booking::create([
                'company_id' => $company->id,
                'booking_number' => "BK-PORTAL-$i",
            ]);
        }

        $this->actingAs($company, 'api');

        $response = $this->getJson('/api/profile/bookings');
        $response->assertStatus(200);
        $json = $response->json();
        $this->assertTrue($json['status']);
        $this->assertEquals(25, $json['pagination']['total']);
        $this->assertEquals(20, $json['pagination']['per_page']);
        $this->assertEquals(1, $json['pagination']['current_page']);
        $this->assertEquals(2, $json['pagination']['total_pages']);
        $this->assertCount(20, $json['data']);
    }
}
