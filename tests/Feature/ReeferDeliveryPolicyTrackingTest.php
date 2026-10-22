<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\Agent\DeliveryPolicyController;
use App\Http\Controllers\Api\Agent\ReeferTripController;
use App\Http\Requests\Api\Agent\DeliveryPolicyRequest;
use App\Models\Agent;
use App\Models\Booking;
use App\Models\BookingContainer;
use App\Models\Car;
use App\Models\CitiesAndRegions;
use App\Models\Container;
use App\Models\DeliveryPolicy;
use App\Models\Driver;
use App\Models\Image;
use App\Models\MoneyTransfer;
use App\Models\ReeferTripLeg;
use App\Services\ReeferTripTrackingService;
use App\Services\ShipmentWorkflow;
use App\Support\ReeferTripStages;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReeferDeliveryPolicyTrackingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        DB::reconnect('sqlite');
        config(['workflows.effective_from' => null]);
        Storage::fake('public');
        $this->createSchema();
        $this->seedBase();
    }

    private function createSchema(): void
    {
        Schema::create('agents', function (Blueprint $t) {
            $t->id();
            $t->string('name')->default('Agent');
            $t->decimal('wallet', 14, 2)->default(0);
            $t->decimal('total_wallet', 14, 2)->default(0);
            $t->timestamps();
        });
        Schema::create('drivers', function (Blueprint $t) {
            $t->id();
            $t->string('name')->nullable();
            $t->string('phone')->nullable();
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
            $t->string('city')->nullable();
            $t->string('address')->nullable();
            $t->timestamps();
        });
        Schema::create('containers', function (Blueprint $t) {
            $t->id();
            $t->string('type')->nullable();
            $t->string('size')->nullable();
            $t->timestamps();
        });
        Schema::create('bookings', function (Blueprint $t) {
            $t->id();
            $t->string('booking_number')->nullable();
            $t->string('type_of_action')->nullable();
            $t->unsignedBigInteger('company_id')->nullable();
            $t->timestamps();
        });
        Schema::create('booking_containers', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('booking_id');
            $t->unsignedBigInteger('container_id')->nullable();
            $t->string('container_no')->nullable();
            $t->unsignedBigInteger('departure_id')->nullable();
            $t->unsignedBigInteger('loading_id')->nullable();
            $t->unsignedBigInteger('aging_id')->nullable();
            $t->decimal('price', 14, 2)->nullable();
            $t->timestamps();
        });
        Schema::create('delivery_policies', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('car_id')->nullable();
            $t->unsignedBigInteger('driver_id')->nullable();
            $t->date('date')->nullable();
            $t->string('address')->nullable();
            $t->decimal('office_commission', 14, 2)->default(0);
            $t->decimal('custody_usd', 14, 2)->nullable();
            $t->boolean('is_settled')->default(0);
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
            $t->date('date')->nullable();
            $t->string('address')->nullable();
            $t->timestamps();
        });
        Schema::create('reefer_trip_legs', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('delivery_policy_id');
            $t->unsignedBigInteger('booking_container_id')->nullable();
            $t->unsignedTinyInteger('stage');
            $t->dateTime('occurred_at')->nullable();
            $t->text('notes')->nullable();
            $t->unsignedBigInteger('recorded_by_agent_id')->nullable();
            $t->timestamps();
            $t->unique(['delivery_policy_id', 'stage']);
        });
        Schema::create('images', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('imageable_id')->nullable();
            $t->string('imageable_type')->nullable();
            $t->string('image')->nullable();
            $t->timestamps();
        });
        Schema::create('company_transportations', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('container_id')->nullable();
            $t->unsignedBigInteger('departure_id')->nullable();
            $t->unsignedBigInteger('loading_id')->nullable();
            $t->unsignedBigInteger('aging_id')->nullable();
            $t->decimal('price', 14, 2)->nullable();
            $t->timestamps();
        });
        Schema::create('agent_expenses', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('agent_id')->nullable();
            $t->unsignedBigInteger('delivery_policy_id')->nullable();
            $t->decimal('value', 14, 2)->default(0);
            $t->timestamp('voided_at')->nullable();
            $t->timestamps();
        });
        Schema::create('log_activities', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('attacher_id')->nullable();
            $t->string('attacher_type')->nullable();
            $t->unsignedBigInteger('log_id')->nullable();
            $t->string('log_type')->nullable();
            $t->integer('container_status')->default(0);
            $t->timestamps();
        });
    }

    private function seedBase(): void
    {
        Agent::create(['id' => 1, 'name' => 'Owner Agent', 'wallet' => 5000, 'total_wallet' => 5000]);
        Agent::create(['id' => 2, 'name' => 'Other Agent', 'wallet' => 5000, 'total_wallet' => 5000]);
        Driver::create(['id' => 1, 'name' => 'Driver One', 'phone' => '01000000001']);
        Car::create(['id' => 1, 'car_number' => 'ABC-123']);
        CitiesAndRegions::create(['id' => 1, 'title' => 'Port Said', 'city' => 'Port Said', 'address' => 'Port']);
        CitiesAndRegions::create(['id' => 2, 'title' => 'Safaga Dest', 'city' => 'Safaga', 'address' => 'Yard']);
        CitiesAndRegions::create(['id' => 3, 'title' => 'Aging City', 'city' => 'Aging', 'address' => 'Age']);
        Container::create(['id' => 1, 'type' => 'reefer', 'size' => '40']);

        Booking::create([
            'id' => 1,
            'booking_number' => 'RF-001',
            'type_of_action' => (string) ShipmentWorkflow::REEFER,
        ]);
        Booking::create([
            'id' => 2,
            'booking_number' => 'EX-001',
            'type_of_action' => (string) ShipmentWorkflow::EXPORT,
        ]);

        BookingContainer::create([
            'id' => 1,
            'booking_id' => 1,
            'container_id' => 1,
            'container_no' => 'REEF001',
            'departure_id' => 1,
            'loading_id' => 2,
            'aging_id' => 3,
        ]);
        BookingContainer::create([
            'id' => 2,
            'booking_id' => 2,
            'container_id' => 1,
            'container_no' => 'EXP001',
            'departure_id' => 1,
            'loading_id' => 2,
            'aging_id' => 3,
        ]);
    }

    private function actingAsAgent(int $id): Agent
    {
        $agent = Agent::findOrFail($id);
        Auth::guard('agent')->setUser($agent);

        return $agent;
    }

    private function createReeferPolicyPayload(array $overrides = []): array
    {
        return array_merge([
            'car_id' => 1,
            'driver_id' => 1,
            'booking_container_ids' => [1],
            'value' => 1000,
            'office_commission' => 100,
            'custody_usd' => 250.50,
            'departure_id' => 1,
            'loading_id' => 2,
            'aging_id' => 3,
            'date' => '2026-10-22',
        ], $overrides);
    }

    public function test_reefer_create_debits_egp_wallet_and_records_usd_only(): void
    {
        $agent = $this->actingAsAgent(1);
        $walletBefore = (float) $agent->wallet;

        $request = DeliveryPolicyRequest::create('/api/agent/create_delivery_policy', 'POST', $this->createReeferPolicyPayload());
        $request->setContainer(app());
        $request->validateResolved();

        $response = app(DeliveryPolicyController::class)->create_delivery_policy($request);
        $this->assertSame(200, $response->getStatusCode());

        $agent->refresh();
        $this->assertSame($walletBefore - 900.0, (float) $agent->wallet);

        $policy = DeliveryPolicy::first();
        $this->assertNotNull($policy);
        $this->assertSame(250.50, (float) $policy->custody_usd);

        $mt = MoneyTransfer::where('delivery_policy_id', $policy->id)->where('type', 3)->first();
        $this->assertSame(1000.0, (float) $mt->value);
        $this->assertSame(1, (int) $mt->transferer_id);

        // No second money transfer for USD
        $this->assertSame(2, MoneyTransfer::where('delivery_policy_id', $policy->id)->count()); // type 3 + type 5 commission
    }

    public function test_update_same_egp_values_does_not_redebit_wallet(): void
    {
        $this->test_reefer_create_debits_egp_wallet_and_records_usd_only();
        $agent = $this->actingAsAgent(1);
        $walletAfterCreate = (float) $agent->wallet;
        $policy = DeliveryPolicy::first();

        $request = Request::create('/api/agent/update_delivery_policy', 'POST', [
            'id' => $policy->id,
            'value' => 1000,
            'office_commission' => 100,
            'custody_usd' => 300,
        ]);

        $response = app(DeliveryPolicyController::class)->update_delivery_policy($request);
        $this->assertSame(200, $response->getStatusCode());

        $agent->refresh();
        $this->assertSame($walletAfterCreate, (float) $agent->wallet);
        $this->assertSame(300.0, (float) $policy->fresh()->custody_usd);
        $this->assertSame(1, MoneyTransfer::where('delivery_policy_id', $policy->id)->where('type', 3)->count());
    }

    public function test_settle_ignores_custody_usd(): void
    {
        $this->test_reefer_create_debits_egp_wallet_and_records_usd_only();
        $agent = $this->actingAsAgent(1);
        $policy = DeliveryPolicy::first();
        $usdBefore = (float) $policy->custody_usd;
        $walletBefore = (float) $agent->wallet;

        // No car expenses → driver_dues = 1000
        $request = \App\Http\Requests\Api\Agent\DeliveryPolicyDetailsRequest::create(
            '/api/agent/settle_delivery_policy',
            'POST',
            ['delivery_policy_id' => $policy->id]
        );
        $request->setContainer(app());
        $request->validateResolved();

        $response = app(DeliveryPolicyController::class)->settle_delivery_policy($request);
        $body = method_exists($response, 'getData') ? $response->getData(true) : [];
        $this->assertSame(200, $response->getStatusCode(), json_encode($body));

        $policy->refresh();
        $agent->refresh();
        $this->assertSame(1, (int) $policy->is_settled);
        $this->assertSame($usdBefore, (float) $policy->custody_usd);
        // settle creates type 4; wallet not adjusted for positive driver_dues in existing code
        $this->assertSame($walletBefore, (float) $agent->wallet);
        $this->assertNotNull($policy->settled_money_transfer);
        $this->assertSame(1000.0, (float) $policy->settled_money_transfer->value);
    }

    public function test_non_reefer_rejects_custody_usd_on_create(): void
    {
        $this->actingAsAgent(1);
        $payload = $this->createReeferPolicyPayload([
            'booking_container_ids' => [2],
            'custody_usd' => 10,
        ]);
        $request = DeliveryPolicyRequest::create('/api/agent/create_delivery_policy', 'POST', $payload);
        $request->setContainer(app());

        try {
            $request->validateResolved();
            $this->fail('Expected validation failure for non-reefer custody_usd');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->assertArrayHasKey('custody_usd', $e->errors());
        }
    }

    public function test_stages_order_timestamps_and_notes_only_stage_11(): void
    {
        $this->test_reefer_create_debits_egp_wallet_and_records_usd_only();
        $agent = $this->actingAsAgent(1);
        $policy = DeliveryPolicy::first();
        $service = app(ReeferTripTrackingService::class);

        $service->upsertStage($policy, $agent, 1, '2026-10-22 08:00:00', null);
        $service->upsertStage($policy, $agent, 11, null, 'Ferry delayed');

        $timeline = $service->buildTimeline($policy->fresh());
        $this->assertCount(11, $timeline);
        $this->assertSame(array_values(ReeferTripStages::all()), array_column($timeline, 'stage'));
        $this->assertSame('2026-10-22 08:00:00', $timeline[0]['occurred_at']);
        $this->assertNull($timeline[10]['occurred_at']);
        $this->assertSame('Ferry delayed', $timeline[10]['notes']);
        $this->assertTrue($timeline[10]['notes_only']);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $service->upsertStage($policy, $agent, 2, null, null);
    }

    public function test_cross_agent_denied_for_trip_details_and_stage_save(): void
    {
        $this->test_reefer_create_debits_egp_wallet_and_records_usd_only();
        $this->actingAsAgent(2);
        $policy = DeliveryPolicy::first();

        $detailsReq = Request::create('/api/agent/reefer_trip_details', 'POST', [
            'delivery_policy_id' => $policy->id,
        ]);
        $details = app(ReeferTripController::class)->reefer_trip_details($detailsReq);
        $this->assertSame(403, $details->getStatusCode());

        $saveReq = Request::create('/api/agent/save_reefer_trip_stage', 'POST', [
            'delivery_policy_id' => $policy->id,
            'stage' => 1,
            'occurred_at' => '2026-10-22 09:00:00',
        ]);
        $save = app(ReeferTripController::class)->save_reefer_trip_stage($saveReq);
        $this->assertContains($save->getStatusCode(), [403, 422]);
        $this->assertSame(0, ReeferTripLeg::count());
    }

    public function test_list_shows_driver_and_loading_destination_only_for_owner(): void
    {
        $this->test_reefer_create_debits_egp_wallet_and_records_usd_only();
        $this->actingAsAgent(1);

        $response = app(ReeferTripController::class)->fetch_reefer_trip_list(Request::create('/api/agent/fetch_reefer_trip_list', 'GET'));
        $payload = $response->getData(true);
        $this->assertTrue($payload['status']);
        $this->assertCount(1, $payload['data']);
        $this->assertSame('Driver One', $payload['data'][0]['driver_name']);
        $this->assertSame('Safaga Dest', $payload['data'][0]['destination']);
        $this->assertArrayNotHasKey('custody_usd', $payload['data'][0]);

        $this->actingAsAgent(2);
        $empty = app(ReeferTripController::class)->fetch_reefer_trip_list(Request::create('/x', 'GET'));
        $this->assertCount(0, $empty->getData(true)['data']);
    }

    public function test_document_upload_authorized_and_denied_cross_agent(): void
    {
        $this->test_reefer_create_debits_egp_wallet_and_records_usd_only();
        $policy = DeliveryPolicy::first();

        $this->actingAsAgent(1);
        $ok = Request::create('/api/agent/upload_reefer_trip_document', 'POST', [
            'delivery_policy_id' => $policy->id,
            'stage' => 1,
        ], [], [
            'document' => UploadedFile::fake()->create('gate.pdf', 20, 'application/pdf'),
        ]);
        $response = app(ReeferTripController::class)->upload_reefer_trip_document($ok);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(1, Image::where('imageable_type', ReeferTripLeg::class)->count());

        $this->actingAsAgent(2);
        $denied = Request::create('/api/agent/upload_reefer_trip_document', 'POST', [
            'delivery_policy_id' => $policy->id,
            'stage' => 2,
        ], [], [
            'document' => UploadedFile::fake()->image('x.jpg'),
        ]);
        $fail = app(ReeferTripController::class)->upload_reefer_trip_document($denied);
        $this->assertContains($fail->getStatusCode(), [403, 422]);
    }

    public function test_create_retry_when_policy_exists_does_not_double_debit(): void
    {
        $this->test_reefer_create_debits_egp_wallet_and_records_usd_only();
        $agent = $this->actingAsAgent(1);
        $wallet = (float) $agent->wallet;

        $request = DeliveryPolicyRequest::create('/api/agent/create_delivery_policy', 'POST', $this->createReeferPolicyPayload());
        $request->setContainer(app());
        $request->validateResolved();
        app(DeliveryPolicyController::class)->create_delivery_policy($request);

        $agent->refresh();
        $this->assertSame($wallet, (float) $agent->wallet);
        $this->assertSame(1, DeliveryPolicy::count());
    }
}
