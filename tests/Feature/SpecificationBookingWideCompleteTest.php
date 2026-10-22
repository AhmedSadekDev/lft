<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\Booking;
use App\Models\BookingContainer;
use App\Models\BookingContainerAgent;
use App\Services\ContainerStageService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SpecificationBookingWideCompleteTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        DB::reconnect('sqlite');
        config(['workflows.effective_from' => null]);

        Schema::create('agents', function (Blueprint $t) {
            $t->id();
            $t->string('name')->default('Agent');
            $t->timestamps();
        });
        Schema::create('bookings', function (Blueprint $t) {
            $t->id();
            $t->string('booking_number')->nullable();
            $t->string('type_of_action')->default('0');
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
            $t->integer('status')->default(0);
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
            $t->timestamp('moved_to_loading_at')->nullable();
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
            $t->timestamp('specification_completed_at')->nullable();
            $t->timestamps();
        });
        Schema::create('daily_booking_containers', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('booking_container_id');
            $t->integer('booking_container_status')->default(0);
            $t->timestamps();
        });
        Schema::create('booking_container_stages', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('booking_container_id');
            $t->integer('type_id')->default(0);
            $t->timestamp('receipts_closed_at')->nullable();
            $t->timestamps();
        });

        Agent::create(['id' => 1, 'name' => 'Agent One']);
        Booking::create(['id' => 1, 'booking_number' => 'MULTI-1', 'type_of_action' => '0']);
        foreach ([1, 2, 3] as $id) {
            BookingContainer::create([
                'id' => $id,
                'booking_id' => 1,
                'status' => 0,
            ]);
        }
        // Assigned to only the first container — booking-wide complete must still finish all three.
        BookingContainerAgent::create([
            'booking_container_id' => 1,
            'agent_id' => 1,
            'stage_type' => 0,
            'booking_container_status' => 0,
        ]);
    }

    public function test_complete_booking_finishes_all_containers_when_agent_assigned_to_one(): void
    {
        $completed = app(ContainerStageService::class)->completeBooking(1, 0, 1);

        $this->assertSame([1, 2, 3], $completed);
        foreach ([1, 2, 3] as $id) {
            $container = BookingContainer::find($id);
            $this->assertSame(1, (int) $container->status);
            $this->assertNotNull($container->specification_completed_at);
            $this->assertTrue(
                BookingContainerAgent::where('booking_container_id', $id)
                    ->where('agent_id', 1)
                    ->where('stage_type', 0)
                    ->exists()
            );
        }
    }

    public function test_complete_booking_rejects_unassigned_agent(): void
    {
        Agent::create(['id' => 2, 'name' => 'Other']);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        app(ContainerStageService::class)->completeBooking(1, 0, 2);
    }
}
