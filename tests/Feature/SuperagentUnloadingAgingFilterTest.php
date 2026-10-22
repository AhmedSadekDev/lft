<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\Superagent\DeliveryPolicyController;
use App\Http\Controllers\Api\Superagent\ShippingAgentController;
use App\Models\Agent;
use App\Models\Booking;
use App\Models\BookingContainer;
use App\Models\Car;
use App\Models\CitiesAndRegions;
use App\Models\Container;
use App\Models\DeliveryPolicy;
use App\Models\Driver;
use App\Models\MoneyTransfer;
use App\Models\Superagent;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SuperagentUnloadingAgingFilterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        DB::reconnect('sqlite');
        $this->createSchema();
        $this->seedData();
    }

    private function createSchema(): void
    {
        Schema::create('superagents', function (Blueprint $t) {
            $t->id();
            $t->string('name')->default('Manager');
            $t->timestamps();
        });
        Schema::create('agents', function (Blueprint $t) {
            $t->id();
            $t->string('name')->default('Agent');
            $t->timestamps();
        });
        Schema::create('drivers', function (Blueprint $t) {
            $t->id();
            $t->string('name')->nullable();
            $t->timestamps();
        });
        Schema::create('cars', function (Blueprint $t) {
            $t->id();
            $t->string('car_number')->nullable();
            $t->timestamps();
        });
        Schema::create('cities_and_regions', function (Blueprint $t) {
            $t->id();
            $t->string('title')->nullable();
            $t->timestamps();
        });
        Schema::create('containers', function (Blueprint $t) {
            $t->id();
            $t->string('type')->nullable();
            $t->string('size')->nullable();
            $t->timestamps();
        });
        Schema::create('companies', function (Blueprint $t) {
            $t->id();
            $t->string('name')->nullable();
            $t->timestamps();
        });
        Schema::create('factories', function (Blueprint $t) {
            $t->id();
            $t->string('name')->nullable();
            $t->timestamps();
        });
        Schema::create('yards', function (Blueprint $t) {
            $t->id();
            $t->string('title')->nullable();
            $t->timestamps();
        });
        Schema::create('shipping_agents', function (Blueprint $t) {
            $t->id();
            $t->string('title')->nullable();
            $t->timestamps();
        });
        Schema::create('branches', function (Blueprint $t) {
            $t->id();
            $t->string('name')->nullable();
            $t->unsignedBigInteger('factory_id')->nullable();
            $t->timestamps();
        });
        Schema::create('bookings', function (Blueprint $t) {
            $t->id();
            $t->string('booking_number')->nullable();
            $t->string('type_of_action')->nullable();
            $t->unsignedBigInteger('company_id')->nullable();
            $t->unsignedBigInteger('factory_id')->nullable();
            $t->unsignedBigInteger('yard_id')->nullable();
            $t->unsignedBigInteger('shipping_agent_id')->nullable();
            $t->timestamps();
        });
        Schema::create('invoices', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('booking_id');
            $t->timestamps();
        });
        Schema::create('booking_containers', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('booking_id');
            $t->unsignedBigInteger('container_id')->nullable();
            $t->string('container_no')->nullable();
            $t->unsignedBigInteger('aging_id')->nullable();
            $t->unsignedBigInteger('loading_id')->nullable();
            $t->unsignedBigInteger('departure_id')->nullable();
            $t->boolean('superagent_specification_approved')->default(0);
            $t->boolean('superagent_loading_approved')->default(0);
            $t->boolean('superagent_unloading_approved')->default(0);
            $t->integer('status')->default(0);
            $t->timestamps();
        });
        Schema::create('delivery_policies', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('car_id')->nullable();
            $t->unsignedBigInteger('driver_id')->nullable();
            $t->date('date')->nullable();
            $t->decimal('office_commission', 14, 2)->default(0);
            $t->decimal('custody_usd', 14, 2)->nullable();
            $t->boolean('is_settled')->default(0);
            $t->string('address')->nullable();
            $t->timestamps();
        });
        Schema::create('delivery_policy_containers', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('delivery_policy_id');
            $t->unsignedBigInteger('booking_container_id');
            $t->timestamps();
        });
        Schema::create('money_transfers', function (Blueprint $t) {
            $t->id();
            $t->decimal('value', 14, 2)->default(0);
            $t->integer('type')->default(3);
            $t->string('transferer_type')->nullable();
            $t->unsignedBigInteger('transferer_id')->nullable();
            $t->string('transfered_type')->nullable();
            $t->unsignedBigInteger('transfered_id')->nullable();
            $t->unsignedBigInteger('delivery_policy_id')->nullable();
            $t->timestamps();
        });
        Schema::create('daily_booking_containers', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('booking_container_id');
            $t->unsignedBigInteger('superagent_id')->nullable();
            $t->integer('booking_container_status')->default(0);
            $t->timestamps();
        });
        Schema::create('booking_papers', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('booking_container_id')->nullable();
            $t->integer('type')->default(0);
            $t->timestamps();
        });
        Schema::create('images', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('imageable_id')->nullable();
            $t->string('imageable_type')->nullable();
            $t->string('image')->nullable();
            $t->timestamps();
        });
        Schema::create('notes', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('attached_id')->nullable();
            $t->string('attached_type')->nullable();
            $t->timestamps();
        });
        Schema::create('booking_container_agents', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('booking_container_id');
            $t->unsignedBigInteger('agent_id');
            $t->integer('stage_type')->default(0);
            $t->integer('booking_container_status')->default(0);
            $t->boolean('superagent_specification_approved')->default(0);
            $t->boolean('superagent_loading_approved')->default(0);
            $t->boolean('superagent_unloading_approved')->default(0);
            $t->boolean('is_in_loading')->default(0);
            $t->timestamps();
        });
    }

    private function seedCity(int $id, string $title): void
    {
        $city = new CitiesAndRegions();
        $city->id = $id;
        $city->setTranslation('title', 'ar', $title);
        $city->setTranslation('title', 'en', $title);
        $city->save();
    }

    private function seedData(): void
    {
        Superagent::create(['id' => 1, 'name' => 'Manager']);
        Agent::create(['id' => 1, 'name' => 'Field Agent']);
        Driver::create(['id' => 1, 'name' => 'Driver']);
        Car::create(['id' => 1, 'car_number' => 'XYZ-1']);
        $this->seedCity(10, 'Safaga Port');
        $this->seedCity(20, 'Sokhna Port');
        Container::create(['id' => 1, 'type' => 'HC', 'size' => '40']);
        Booking::create(['id' => 1, 'booking_number' => 'B-1', 'type_of_action' => '0']);
        Booking::create(['id' => 2, 'booking_number' => 'B-2', 'type_of_action' => '0']);

        BookingContainer::create([
            'id' => 1,
            'booking_id' => 1,
            'container_id' => 1,
            'container_no' => 'C1',
            'aging_id' => 10,
            'superagent_specification_approved' => 1,
            'superagent_loading_approved' => 1,
            'superagent_unloading_approved' => 0,
        ]);
        BookingContainer::create([
            'id' => 2,
            'booking_id' => 2,
            'container_id' => 1,
            'container_no' => 'C2',
            'aging_id' => 20,
            'superagent_specification_approved' => 1,
            'superagent_loading_approved' => 1,
            'superagent_unloading_approved' => 0,
        ]);

        $policy = DeliveryPolicy::create([
            'car_id' => 1,
            'driver_id' => 1,
            'date' => '2026-10-22',
            'is_settled' => 0,
        ]);
        $policy->booking_containers()->attach(1);
        MoneyTransfer::create([
            'value' => 500,
            'type' => 3,
            'transferer_type' => Agent::class,
            'transferer_id' => 1,
            'transfered_type' => Driver::class,
            'transfered_id' => 1,
            'delivery_policy_id' => $policy->id,
        ]);
    }

    public function test_unloading_aging_assignments_group_like_shipping_chips(): void
    {
        Auth::guard('superagent')->setUser(Superagent::find(1));

        $response = app(ShippingAgentController::class)->unloading_aging_assignments(Request::create('/x', 'GET'));
        $payload = $response->getData(true);

        $this->assertTrue($payload['status'] ?? false, json_encode($payload));
        $this->assertCount(2, $payload['data']);
        $titles = collect($payload['data'])->pluck('title')->all();
        $this->assertContains('Safaga Port', $titles);
        $this->assertContains('Sokhna Port', $titles);

        $filtered = app(ShippingAgentController::class)->unloading_aging_assignments(
            Request::create('/x', 'GET', ['aging_id' => 10])
        );
        $filteredPayload = $filtered->getData(true);
        $this->assertCount(1, $filteredPayload['data']);
        $this->assertSame(10, (int) $filteredPayload['data'][0]['id']);
        $this->assertSame('Safaga Port', $filteredPayload['data'][0]['title']);
    }

    public function test_group_by_aging_query_on_legacy_unloading_assignments(): void
    {
        Auth::guard('superagent')->setUser(Superagent::find(1));
        $response = app(ShippingAgentController::class)->unloading_assignments(
            Request::create('/x', 'GET', ['group_by' => 'aging'])
        );
        $this->assertTrue($response->getData(true)['status']);
        $this->assertCount(2, $response->getData(true)['data']);
    }

    public function test_manager_can_list_agent_created_delivery_policies_filtered_by_aging(): void
    {
        Auth::guard('superagent')->setUser(Superagent::find(1));

        $all = app(DeliveryPolicyController::class)->fetch_delivery_policies(Request::create('/x', 'GET'));
        $this->assertCount(1, $all->getData(true)['data']);
        $this->assertSame('Safaga Port', $all->getData(true)['data'][0]['aging_port']);
        $this->assertSame('Field Agent', $all->getData(true)['data'][0]['agent_name']);

        $filtered = app(DeliveryPolicyController::class)->fetch_delivery_policies(
            Request::create('/x', 'GET', ['aging_id' => 20])
        );
        $this->assertCount(0, $filtered->getData(true)['data']);
    }
}
