<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\AgentExpense;
use App\Models\BookingContainer;
use App\Models\BookingContainerStage;
use App\Services\ContainerStageService;
use App\Services\StageExpenseService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class ParallelContainerStagesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Isolated schema: never migrate, truncate, or connect to the project's real database.
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('invoices', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('booking_id');
        });
        Schema::create('delivery_policies', fn (Blueprint $t) => $t->id());
        foreach (['companies', 'factories', 'branches', 'containers'] as $table) {
            Schema::create($table, fn (Blueprint $t) => $t->id());
        }
        Schema::create('delivery_policy_containers', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('delivery_policy_id');
            $t->unsignedBigInteger('booking_container_id');
        });
        Schema::create('agents', function (Blueprint $t) {
            $t->id();
            $t->decimal('wallet', 12, 2)->default(1000);
            $t->timestamps();
        });
        Schema::create('bookings', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('yard_id')->nullable();
            $t->unsignedBigInteger('shipping_agent_id')->nullable();
            $t->string('booking_number')->default('B-1');
            $t->timestamps();
        });
        foreach (['yards', 'shipping_agents'] as $table) {
            Schema::create($table, function (Blueprint $t) {
                $t->id();
                $t->text('title')->nullable();
                $t->timestamps();
            });
        }
        Schema::create('services', function (Blueprint $t) {
            $t->id();
            $t->string('name')->nullable();
            $t->timestamps();
        });
        Schema::create('notes', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('attached_id');
            $t->string('attached_type');
            $t->timestamps();
        });
        Schema::create('booking_papers', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('booking_container_id');
            $t->unsignedBigInteger('booking_id');
            $t->unsignedBigInteger('agent_id')->nullable();
            $t->integer('type');
            $t->timestamps();
        });
        Schema::create('log_activities', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('attacher_id');
            $t->string('attacher_type');
            $t->unsignedBigInteger('log_id');
            $t->string('log_type');
            $t->integer('container_status');
            $t->timestamps();
        });
        Schema::create('booking_containers', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('booking_id')->default(1);
            $t->integer('status')->default(1);
            $t->boolean('is_in_loading')->default(true);
            $t->timestamp('moved_to_loading_at')->nullable();
            foreach (ContainerStageService::NAMES as $name) {
                $t->boolean('superagent_'.$name.'_approved')->default($name === 'specification');
                $t->timestamp($name.'_completed_at')->nullable();
                $t->timestamp($name.'_approved_at')->nullable();
            }
            $t->timestamps();
        });
        Schema::create('booking_container_agents', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('booking_container_id');
            $t->unsignedBigInteger('agent_id');
            $t->integer('booking_container_status')->nullable();
            $t->boolean('is_in_loading')->default(false);
            $t->timestamp('moved_to_loading_at')->nullable();
            foreach (ContainerStageService::NAMES as $name) {
                $t->boolean('superagent_'.$name.'_approved')->default(false);
                $t->timestamp($name.'_completed_at')->nullable();
                $t->timestamp($name.'_approved_at')->nullable();
            }
            $t->timestamps();
        });
        Schema::create('daily_booking_containers', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('booking_container_id');
            $t->integer('booking_container_status');
            $t->boolean('is_in_loading')->default(false);
            $t->timestamp('moved_to_loading_at')->nullable();
            foreach (ContainerStageService::NAMES as $name) {
                $t->boolean('superagent_'.$name.'_approved')->default(false);
            }
            $t->timestamps();
        });
        Schema::create('agent_expenses', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('agent_id');
            $t->unsignedBigInteger('booking_container_id')->nullable();
            $t->unsignedBigInteger('booking_id')->nullable();
            $t->unsignedBigInteger('delivery_policy_id')->nullable();
            $t->integer('type_id')->nullable();
            $t->integer('type')->default(2);
            $t->decimal('value', 12, 2);
            $t->string('image_agent_expenses')->nullable();
            $t->string('notes')->nullable();
            $t->unsignedBigInteger('service_id')->nullable();
            $t->boolean('admin_approval')->default(false);
            $t->timestamps();
        });
        (require database_path('migrations/2026_09_13_000001_add_parallel_container_stages.php'))->up();
        Agent::create(['id' => 1, 'wallet' => 1000]);
        Agent::create(['id' => 2, 'wallet' => 1000]);
        DB::table('yards')->insert(['id' => 1, 'title' => '{"en":"Yard","ar":"Yard"}']);
        DB::table('shipping_agents')->insert(['id' => 1, 'title' => '{"en":"Shipping","ar":"Shipping"}']);
        DB::table('bookings')->insert(['id' => 1, 'yard_id' => 1, 'shipping_agent_id' => 1]);
        DB::table('services')->insert(['id' => 1, 'name' => 'Service']);
        BookingContainer::create(['id' => 1]);
        app(ContainerStageService::class)->assign(1, [1], 1);
    }

    public function test_superagent_done_actions_advance_all_stages_without_an_agent(): void
    {
        Schema::table('delivery_policy_containers', fn (Blueprint $t) => $t->timestamps());
        DB::table('booking_container_agents')->delete();
        BookingContainer::find(1)->update([
            'status' => 0, 'superagent_specification_approved' => false, 'is_in_loading' => false,
        ]);
        DB::table('daily_booking_containers')->insert([
            'booking_container_id' => 1, 'booking_container_status' => 0,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $superagent = new \App\Models\Superagent;
        $superagent->forceFill(['id' => 7]);
        $this->actingAs($superagent, 'superagent');

        foreach (ContainerStageService::NAMES as $type => $name) {
            $url = '/api/superagent/booking/done_'.$name;
            $response = $this->postJson($url, ['booking_container_id' => 1]);
            $this->assertSame(200, $response->status(), $response->getContent());
            $container = BookingContainer::find(1);
            $this->assertSame($type + 1, (int) $container->status);
            $this->assertSame(1, (int) $container->{'superagent_'.$name.'_approved'});
            $this->assertNotNull($container->{$name.'_completed_at'});
            $this->assertNotNull($container->{$name.'_approved_at'});
            $this->assertSame($type === 0 ? 0 : 1, (int) $container->is_in_loading);
            $this->assertDatabaseHas('daily_booking_containers', [
                'booking_container_id' => 1, 'booking_container_status' => $type + 1,
                'superagent_'.$name.'_approved' => 1,
            ]);

            $before = $container->getAttributes();
            $this->postJson($url, ['booking_container_id' => 1])->assertOk();
            $this->assertSame($before, BookingContainer::find(1)->getAttributes());
            $this->assertDatabaseCount('daily_booking_containers', 1);
            if ($type === 0) {
                $this->assertNull($container->moved_to_loading_at);
                $this->assertDatabaseHas('daily_booking_containers', [
                    'booking_container_id' => 1, 'is_in_loading' => 0, 'moved_to_loading_at' => null,
                ]);
                $this->getJson('/api/superagent/booking/waiting')->assertOk()
                    ->assertJsonPath('data.data.0.id', 1);
                $this->getJson('/api/superagent/booking/loading')->assertOk()
                    ->assertJsonPath('data.data', []);
                $this->postJson('/api/superagent/booking/done_loading', ['booking_container_id' => 1])
                    ->assertStatus(409);
                app(ContainerStageService::class)->moveToLoading([1], true);
            }
        }
        $this->assertDatabaseCount('booking_container_agents', 0);
    }

    public function test_superagent_done_actions_cannot_skip_specification(): void
    {
        BookingContainer::find(1)->update([
            'status' => 0, 'superagent_specification_approved' => false, 'is_in_loading' => false,
        ]);
        $superagent = new \App\Models\Superagent;
        $superagent->forceFill(['id' => 7]);
        $this->actingAs($superagent, 'superagent');

        foreach (['loading', 'unloading'] as $name) {
            $this->postJson('/api/superagent/booking/done_'.$name, ['booking_container_id' => 1])
                ->assertStatus(409);
        }
        $this->assertSame(0, (int) BookingContainer::find(1)->status);
    }

    private function receipt(string $key = 'receipt-1', int $agent = 1, int $type = 1, int $amount = 100): AgentExpense
    {
        return app(StageExpenseService::class)->create($agent, ['booking_container_id' => 1, 'type_id' => $type, 'value' => $amount], $key, hash('sha256', "$type:$amount"), fn () => null);
    }

    public function test_general_expense_cannot_drop_booking_or_stage_context_and_debit_wallet(): void
    {
        $this->actingAs(Agent::find(1), 'agent');
        foreach ([['type_id' => 2], ['booking_id' => 1], ['booking_id' => 1, 'type_id' => 2]] as $context) {
            $response = $this->postJson('/api/agent/make_general_expenses', $context + [
                'value' => 100, 'service_id' => 1, 'request_key' => 'missing-container',
            ]);
            $this->assertFalse($response->json('status'));
            $this->assertSame(1000.0, (float) Agent::find(1)->wallet);
            $this->assertDatabaseCount('agent_expenses', 0);
        }
        $this->postJson('/api/agent/make_general_expenses', [
            'value' => 100, 'service_id' => 1, 'request_key' => 'general-only',
        ])->assertOk()->assertJsonPath('status', true);
        $this->assertSame(900.0, (float) Agent::find(1)->wallet);
    }

    public function test_multiple_unloading_receipts_remain_visible_with_their_images_after_approval(): void
    {
        $service = app(ContainerStageService::class);
        $service->approve(1, 1);
        $service->assign(1, [1], 2);
        $expenses = collect();
        foreach ([1, 2, 3] as $number) {
            $expenses->push(app(StageExpenseService::class)->create(1, [
                'booking_container_id' => 1, 'type_id' => 2, 'value' => 100, 'service_id' => 1,
            ], 'unloading-'.$number, hash('sha256', (string) $number), fn () => 'receipt-'.$number.'.jpg'));
        }
        $service->approve(1, 2);
        $this->assertSame(700.0, (float) Agent::find(1)->wallet);
        $this->assertSame($expenses->pluck('id')->all(), AgentExpense::forBooking(1)->orderBy('id')->pluck('id')->all());
        $superagent = new \App\Models\Superagent;
        $superagent->forceFill(['id' => 7]);
        $this->actingAs($superagent, 'superagent');
        foreach (['/api/superagent/containers-expenses', '/api/superagent/booking/stage_receipts'] as $url) {
            $response = $this->getJson($url.'?booking_container_id=1&type_id=2')->assertOk();
            $response->assertJsonCount(3, 'data.expenses');
            foreach ($expenses as $index => $expense) {
                $response->assertJsonPath('data.expenses.'.$index.'.id', $expense->id)
                    ->assertJsonPath('data.expenses.'.$index.'.image', asset('Admin/images/expenses/'.$expense->image_agent_expenses));
            }
        }
    }

    public function test_dashboard_can_return_one_container_step_by_step_to_specification(): void
    {
        $service = app(ContainerStageService::class);
        $sibling = BookingContainer::create(['status' => 2, 'superagent_loading_approved' => 1]);
        $service->approve(1, 1);
        $service->assign(1, [2], 2);
        $service->approve(1, 2);
        DB::table('daily_booking_containers')->insert([
            'booking_container_id' => 1, 'booking_container_status' => 3,
            'superagent_specification_approved' => 1, 'superagent_loading_approved' => 1,
            'superagent_unloading_approved' => 1, 'is_in_loading' => 1,
        ]);

        $service->returnToPreviousStage(1, 3);
        $this->assertSame(2, (int) BookingContainer::find(1)->status);
        $this->assertFalse(BookingContainer::find(1)->isStageCompleted(2));
        $service->returnToPreviousStage(1, 2);
        $container = BookingContainer::find(1);
        $this->assertSame(1, (int) $container->status);
        $this->assertSame(1, (int) $container->is_in_loading);
        $this->assertSame(0, (int) $container->superagent_loading_approved);
        $this->assertFalse($service->assignments(1, 2)->exists());
        $this->assertTrue($service->visibleContainers(1, 1)->whereKey(1)->exists());

        $service->returnToPreviousStage(1, 1);
        $container->refresh();
        $this->assertSame(0, (int) $container->status);
        $this->assertSame(0, (int) $container->is_in_loading);
        $this->assertNull($container->moved_to_loading_at);
        $this->assertSame(0, (int) $container->superagent_specification_approved);
        $this->assertFalse($service->assignments(1, 1)->exists());
        $daily = DB::table('daily_booking_containers')->where('booking_container_id', 1)->first();
        $this->assertSame(0, (int) $daily->booking_container_status);
        $this->assertSame(0, (int) $daily->superagent_specification_approved);
        $this->assertSame(0, (int) $daily->superagent_loading_approved);
        $this->assertSame(0, (int) $daily->superagent_unloading_approved);
        $this->assertSame(2, (int) $sibling->fresh()->status);
        try {
            $service->returnToPreviousStage(1, 0);
            $this->fail('Specification cannot move backwards.');
        } catch (HttpException $exception) {
            $this->assertSame(422, $exception->getStatusCode());
        }
    }

    public function test_direct_rewind_cannot_bypass_invoice_protection(): void
    {
        $service = app(ContainerStageService::class);
        $service->approve(1, 1);
        DB::table('invoices')->insert(['booking_id' => 1]);
        $before = BookingContainer::find(1)->getAttributes();

        try {
            $service->rewind(1, 1);
            $this->fail('An invoiced booking cannot be rewound.');
        } catch (HttpException $exception) {
            $this->assertSame(409, $exception->getStatusCode());
            $this->assertSame(__('container_stages.invoiced'), $exception->getMessage());
        }

        $this->assertSame($before, BookingContainer::find(1)->getAttributes());
        $this->assertTrue($service->assignments(1, 1)->exists());
    }

    public function test_dashboard_rewind_rejects_stale_requests_and_invoiced_bookings(): void
    {
        $service = app(ContainerStageService::class);
        $this->assertConflict(fn () => $service->returnToPreviousStage(1, 2));
        DB::table('invoices')->insert(['booking_id' => 1]);
        $this->assertConflict(fn () => $service->returnToPreviousStage(1, 1));
        $this->assertTrue($service->assignments(1, 1)->exists());
        $this->assertSame(1, (int) BookingContainer::find(1)->status);
    }

    public function test_dashboard_previous_stage_endpoint_validates_and_returns_only_the_selected_container(): void
    {
        $this->withoutMiddleware(\Spatie\Permission\Middlewares\PermissionMiddleware::class);
        $user = new \App\Models\User;
        $user->forceFill(['id' => 1]);
        $this->actingAs($user, 'web');
        $url = route('booking-containers.previous-stage', 1);
        $this->postJson($url)->assertUnprocessable();
        $this->postJson($url, ['expected_status' => 3])->assertStatus(409);
        $this->postJson($url, ['expected_status' => 1])->assertOk();
        $this->assertSame(0, (int) BookingContainer::find(1)->status);
        $this->postJson($url, ['expected_status' => 1])->assertStatus(409);
    }

    public function test_dashboard_rewind_allows_return_with_expenses_and_reopens_closed_receipts(): void
    {
        $service = app(ContainerStageService::class);
        $this->receipt();
        $service->returnToPreviousStage(1, 1);
        $this->assertSame(0, (int) BookingContainer::find(1)->status);
        $this->assertDatabaseHas('agent_expenses', ['booking_container_id' => 1]);

        $service->approve(1, 0);
        $service->moveToLoading([1], true);
        $service->approve(1, 1);
        $service->assign(1, [2], 2);
        $stage = BookingContainerStage::where('booking_container_id', 1)->where('type_id', 1)->first();
        $service->closeReceipts(1, 1, 7, $stage->version);
        $this->assertNotNull($stage->fresh()->receipts_closed_at);

        $service->returnToPreviousStage(1, 2);
        $this->assertSame(1, (int) BookingContainer::find(1)->status);
        $this->assertNull($stage->fresh()->receipts_closed_at);
        $this->assertDatabaseHas('agent_expenses', ['booking_container_id' => 1]);
    }

    public static function assignmentStages(): array
    {
        return ['specification' => [0], 'loading' => [1], 'unloading' => [2]];
    }

    /** @dataProvider assignmentStages */
    public function test_superagent_done_flag_tracks_completion_before_approval(int $type): void
    {
        Schema::table('booking_containers', fn (Blueprint $t) => $t->date('arrival_date')->nullable());
        Schema::table('delivery_policy_containers', fn (Blueprint $t) => $t->timestamps());
        $values = [
            'status' => $type,
            'superagent_specification_approved' => $type > 0,
            'superagent_loading_approved' => $type > 1,
        ];
        BookingContainer::find(1)->update($values);
        $superagent = new \App\Models\Superagent;
        $superagent->forceFill(['id' => 7]);
        $this->actingAs($superagent, 'superagent');
        $name = ContainerStageService::NAMES[$type];
        $url = '/api/superagent/booking/'.$name;
        $flag = 'data.data.0.is_'.$name.'_done';
        $this->getJson($url)->assertOk()->assertJsonPath($flag, 0)
            ->assertJsonPath('data.data.0.booking_containers.0.is_'.$name.'_done', 0);

        app(ContainerStageService::class)->complete(1, $type);
        $this->assertSame(0, (int) BookingContainer::find(1)->{'superagent_'.$name.'_approved'});
        $this->getJson($url)->assertOk()->assertJsonPath($flag, 1)
            ->assertJsonPath('data.data.0.booking_containers.0.is_'.$name.'_done', 1);

        // Legacy completion without timestamps must produce the same result.
        BookingContainer::find(1)->update([$name.'_completed_at' => null]);
        $this->getJson($url)->assertOk()->assertJsonPath($flag, 1);

        // Siblings stay in one booking; completion requires every matching container.
        $pending = BookingContainer::create($values);
        $response = $this->getJson($url)->assertOk()->assertJsonPath($flag, 0);
        $response->assertJsonCount(1, 'data.data')->assertJsonPath('data.meta.total', 1)
            ->assertJsonPath('data.data.0.booking_id', 1)
            ->assertJsonCount(2, 'data.data.0.booking_containers');
        $rows = collect($response->json('data.data.0.booking_containers'))->keyBy('id');
        $this->assertSame(1, $rows[1]['is_'.$name.'_done']);
        $this->assertSame(0, $rows[$pending->id]['is_'.$name.'_done']);
        $this->getJson($url.'?per_page=1')->assertOk()
            ->assertJsonCount(2, 'data.data.0.booking_containers')
            ->assertJsonPath('data.meta.total', 1);
        $this->getJson($url.'?per_page=1&page=2')->assertOk()
            ->assertJsonCount(0, 'data.data')
            ->assertJsonPath('data.meta.total', 1);
        app(ContainerStageService::class)->complete($pending->id, $type);
        $this->getJson($url)->assertOk()->assertJsonPath($flag, 1);
        app(ContainerStageService::class)->rewind($pending->id, $type);
        $this->getJson($url)->assertOk()->assertJsonPath($flag, 0);
    }

    public function test_superagent_missions_group_containers_before_paginating_bookings(): void
    {
        Schema::table('booking_containers', fn (Blueprint $t) => $t->date('arrival_date')->nullable());
        Schema::table('delivery_policy_containers', fn (Blueprint $t) => $t->timestamps());
        $waiting = BookingContainer::create(['is_in_loading' => false]);
        $otherWaiting = BookingContainer::create(['is_in_loading' => false]);
        DB::table('bookings')->insert(['id' => 2, 'booking_number' => 'B-2']);
        $otherBookingContainer = BookingContainer::create(['booking_id' => 2]);
        $superagent = new \App\Models\Superagent;
        $superagent->forceFill(['id' => 7]);
        $this->actingAs($superagent, 'superagent');

        $this->getJson('/api/superagent/booking/missions/all?per_page=1')->assertOk()
            ->assertJsonPath('data.meta.total', 2)
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.id', 2)
            ->assertJsonPath('data.data.0.booking_id', 2)
            ->assertJsonPath('data.data.0.booking_containers.0.id', $otherBookingContainer->id);
        $response = $this->getJson('/api/superagent/booking/missions/all?per_page=1&page=2')->assertOk()
            ->assertJsonPath('data.data.0.id', 1)
            ->assertJsonPath('data.data.0.booking_id', 1)
            ->assertJsonCount(3, 'data.data.0.booking_containers');
        $containers = collect($response->json('data.data.0.booking_containers'))->keyBy('id');
        $this->assertSame('loading', $containers[1]['type']);
        $this->assertSame('waiting', $containers[$waiting->id]['type']);
        $this->assertSame('waiting', $containers[$otherWaiting->id]['type']);

        $this->getJson('/api/superagent/booking/missions/all?stage_type=waiting&per_page=1')->assertOk()
            ->assertJsonPath('data.meta.total', 1)
            ->assertJsonCount(2, 'data.data.0.booking_containers');
        $this->getJson('/api/superagent/booking/waiting?per_page=1')->assertOk()
            ->assertJsonPath('data.meta.total', 1)
            ->assertJsonCount(2, 'data.data.0.booking_containers');

        DB::table('invoices')->insert(['booking_id' => 1]);
        $this->getJson('/api/superagent/booking/missions/all')->assertOk()
            ->assertJsonPath('data.meta.total', 1)
            ->assertJsonPath('data.data.0.id', 2);
        $this->getJson('/api/superagent/booking/waiting')->assertOk()
            ->assertJsonCount(0, 'data.data');
    }

    public function test_superagent_waiting_done_stays_one_after_waiting(): void
    {
        Schema::table('booking_containers', fn (Blueprint $t) => $t->date('arrival_date')->nullable());
        Schema::table('delivery_policy_containers', fn (Blueprint $t) => $t->timestamps());
        $superagent = new \App\Models\Superagent;
        $superagent->forceFill(['id' => 7]);
        $this->actingAs($superagent, 'superagent');
        $service = app(ContainerStageService::class);
        $service->moveToLoading([1], false);
        $this->getJson('/api/superagent/booking/waiting')->assertOk()
            ->assertJsonPath('data.data.0.is_specification_done', 1)
            ->assertJsonPath('data.data.0.is_waiting_done', 1)
            ->assertJsonPath('data.data.0.booking_containers.0.is_waiting_done', 1)
            ->assertJsonPath('data.data.0.is_loading_done', 0)
            ->assertJsonPath('data.data.0.is_unloading_done', 0);
        $service->moveToLoading([1], true);
        $this->getJson('/api/superagent/booking/loading')->assertOk()
            ->assertJsonPath('data.data.0.is_waiting_done', 1)
            ->assertJsonPath('data.data.0.booking_containers.0.is_waiting_done', 1);
        $service->moveToLoading([1], false);
        $this->getJson('/api/superagent/booking/waiting')->assertOk()
            ->assertJsonPath('data.data.0.is_waiting_done', 1);
        $service->moveToLoading([1], true);
        $service->approve(1, 1, 7);
        $this->getJson('/api/superagent/booking/unloading')->assertOk()
            ->assertJsonPath('data.data.0.is_waiting_done', 1)
            ->assertJsonPath('data.data.0.booking_containers.0.is_waiting_done', 1);
    }

    public function test_details_show_container_700_loaded_while_booking_425_is_incomplete(): void
    {
        Schema::table('booking_containers', fn (Blueprint $t) => $t->date('arrival_date')->nullable());
        Schema::table('delivery_policy_containers', fn (Blueprint $t) => $t->timestamps());
        DB::table('bookings')->insert(['id' => 425, 'booking_number' => 'CFA0962788']);
        foreach (range(696, 701) as $id) {
            DB::table('booking_containers')->insert([
                'id' => $id,
                'booking_id' => 425,
                'status' => $id === 700 ? 2 : 1,
                'loading_completed_at' => $id === 700 ? '2026-09-27 22:01:56' : null,
                'superagent_specification_approved' => 1,
                'superagent_loading_approved' => 0,
                'is_in_loading' => 1,
            ]);
        }
        $superagent = new \App\Models\Superagent;
        $superagent->forceFill(['id' => 7]);
        $this->actingAs($superagent, 'superagent');

        foreach (['booking_id=425', 'id=425'] as $query) {
            $response = $this->getJson('/api/superagent/booking/details?type_id=1&'.$query)
                ->assertOk()
                ->assertJsonPath('data.id', 425)
                ->assertJsonPath('data.is_loading_done', 0)
                ->assertJsonPath('data.is_waiting_done', 1)
                ->assertJsonCount(6, 'data.booking_containers');
            $containers = collect($response->json('data.booking_containers'))->keyBy('id');
            foreach (range(696, 701) as $id) {
                $this->assertSame($id === 700 ? 1 : 0, $containers[$id]['is_loading_done']);
                $this->assertSame(1, $containers[$id]['is_waiting_done']);
            }
        }

        foreach (['stage=loading', 'type_id=1'] as $stageQuery) {
            foreach ([696 => 0, 700 => 1] as $containerId => $done) {
                $this->getJson('/api/superagent/booking/details?booking_container_id='.$containerId.'&'.$stageQuery)
                    ->assertOk()
                    ->assertJsonPath('data.id', 425)
                    ->assertJsonPath('data.is_loading_done', $done)
                    ->assertJsonPath('data.is_waiting_done', 1)
                    ->assertJsonCount(1, 'data.booking_containers')
                    ->assertJsonPath('data.booking_containers.0.id', $containerId)
                    ->assertJsonPath('data.booking_containers.0.is_loading_done', $done)
                    ->assertJsonCount(1, 'data.loading_containers')
                    ->assertJsonPath('data.loading_containers.0.id', $containerId);
            }
        }
    }

    private function prepareAssignmentStage(int $type): ContainerStageService
    {
        BookingContainer::find(1)->update([
            'status' => $type,
            'superagent_specification_approved' => $type > 0,
            'superagent_loading_approved' => $type > 1,
        ]);
        $service = app(ContainerStageService::class);
        $this->assertSame($type, $service->assign(1, [1]));
        $this->actingAs(Agent::find(1), 'agent');

        return $service;
    }

    /** @dataProvider assignmentStages */
    public function test_assignments_without_grouping_relations_remain_visible_in_every_stage(int $type): void
    {
        $service = $this->prepareAssignmentStage($type);
        DB::table('bookings')->where('id', 1)->update(['yard_id' => null, 'shipping_agent_id' => null]);
        $name = ContainerStageService::NAMES[$type];
        $path = $type === 0 ? 'data.0.bookings.0.booking_containers.0' : 'data.0.booking_containers.0';
        $this->getJson('/api/agent/booking/fetch_'.$name.'_assignments')->assertOk()
            ->assertJsonPath('data.0.id', 0)->assertJsonPath('data.0.title', 'غير محدد')
            ->assertJsonPath($path.'.id', 1)->assertJsonPath($path.'.type_id', $type)
            ->assertJsonPath($path.'.can_complete_stage', true);
        $service->assign(1, [2], $type);
        $this->getJson('/api/agent/booking/fetch_'.$name.'_assignments')->assertOk()->assertJsonPath('data', []);
        $this->assertSame(1, $service->visibleContainers(2, $type)->count());
    }

    /** @dataProvider assignmentStages */
    public function test_legacy_assignments_have_consistent_visibility_and_permissions(int $type): void
    {
        $service = $this->prepareAssignmentStage($type);
        $service->assignments(1, $type)->update(['stage_type' => null]);
        $this->assertSame(1, $service->visibleContainers(1, $type)->count());
        $service->assertAssigned(BookingContainer::find(1), $type, 1);
        $service->assign(1, [1], $type);
        $this->assertSame(1, $service->assignments(1, $type)->count());
        $this->assertSame(0, DB::table('booking_container_agents')->whereNull('stage_type')->count());
    }

    /** @dataProvider assignmentStages */
    public function test_closed_or_approved_stages_cannot_accept_invisible_assignments(int $type): void
    {
        $service = $this->prepareAssignmentStage($type);
        $service->closeReceipts(1, $type, 7, 1);
        $this->assertConflict(fn () => $service->assign(1, [2], $type));
        $this->getJson('/api/agent/booking/fetch_'.ContainerStageService::NAMES[$type].'_assignments')
            ->assertOk()->assertJsonPath('data', []);
        BookingContainerStage::query()->delete();
        BookingContainer::find(1)->update(['superagent_'.ContainerStageService::NAMES[$type].'_approved' => true]);
        $this->assertConflict(fn () => $service->assign(1, [2], $type));
        $this->assertSame(1, $service->assignments(1, $type)->where('agent_id', 1)->count());
    }

    /** @dataProvider assignmentStages */
    public function test_rewound_stages_reappear_with_completion_enabled(int $type): void
    {
        $service = $this->prepareAssignmentStage($type);
        // Specification cannot rewind after the following phase has been assigned.
        if ($type === 0) {
            $service->assignments(1, 1)->delete();
        }
        $service->complete(1, $type, 1);
        $service->rewind(1, $type);
        $path = $type === 0 ? 'data.0.bookings.0.booking_containers.0' : 'data.0.booking_containers.0';
        $this->getJson('/api/agent/booking/fetch_'.ContainerStageService::NAMES[$type].'_assignments')->assertOk()
            ->assertJsonPath($path.'.can_complete_stage', true)->assertJsonPath($path.'.stage_status', 'pending');
        $this->assertSame($type, (int) $service->assignments(1, $type)->first()->booking_container_status);
    }

    public function test_approved_loading_rewind_restores_loading_visibility_even_if_loading_flag_was_cleared(): void
    {
        $service = $this->prepareAssignmentStage(1);
        $service->approve(1, 1);
        BookingContainer::find(1)->update(['is_in_loading' => false]);
        $service->rewind(1, 1);
        $this->getJson('/api/agent/booking/fetch_loading_assignments')->assertOk()
            ->assertJsonPath('data.0.booking_containers.0.can_complete_stage', true)
            ->assertJsonPath('data.0.booking_containers.0.is_in_loading', 1);
    }

    public function test_waiting_assignment_appears_only_after_move_to_loading(): void
    {
        $service = $this->prepareAssignmentStage(1);
        BookingContainer::find(1)->update(['is_in_loading' => false]);
        $service->assign(1, [1]);
        $this->getJson('/api/agent/booking/fetch_loading_assignments')->assertOk()->assertJsonPath('data', []);
        $service->moveToLoading([1], true);
        $this->getJson('/api/agent/booking/fetch_loading_assignments')->assertOk()
            ->assertJsonPath('data.0.booking_containers.0.id', 1);
    }

    public function test_invoice_hides_all_phases_from_every_assigned_agent(): void
    {
        $service = app(ContainerStageService::class);
        BookingContainer::find(1)->update(['superagent_specification_approved' => false]);
        $service->assign(1, [1, 2], 0);
        BookingContainer::find(1)->update(['superagent_specification_approved' => true]);
        $service->assign(1, [1, 2], 1);
        $service->approve(1, 1);
        $service->assign(1, [1, 2], 2);
        DB::table('invoices')->insert(['booking_id' => 1]);

        foreach ([1, 2] as $agentId) {
            $this->actingAs(Agent::find($agentId), 'agent');
            foreach (ContainerStageService::NAMES as $type => $name) {
                $this->assertSame(0, $service->visibleContainers($agentId, $type)->count());
                $this->getJson('/api/agent/booking/fetch_'.$name.'_assignments')
                    ->assertOk()->assertJsonPath('data', []);
            }
            $this->getJson('/api/agent/booking/fetch_home_statistics')->assertOk()
                ->assertJsonPath('data.loading_assignments.daily_loading_assignments_count', 0);
            $this->postJson('/api/agent/booking/fetch_bookings')->assertOk()->assertJsonPath('data', []);
            $this->getJson('/api/agent/booking/fetch_booking_containers')->assertOk()->assertJsonPath('data', []);
        }

        // The dashboard still has the order, containers and original assignments.
        $this->assertNotNull(\App\Models\Booking::find(1));
        $this->assertSame(1, BookingContainer::count());
        $this->assertSame(6, DB::table('booking_container_agents')->count());
    }

    public function test_invoice_blocks_stale_agent_writes_and_persisted_expense_and_policy_references(): void
    {
        $expense = $this->receipt();
        DB::table('delivery_policies')->insert(['id' => 1]);
        DB::table('delivery_policy_containers')->insert(['delivery_policy_id' => 1, 'booking_container_id' => 1]);
        DB::table('invoices')->insert(['booking_id' => 1]);
        $this->actingAs(Agent::find(1), 'agent');

        $requests = [
            ['booking/done_specification', ['booking_id' => 1]],
            ['booking/done_loading', ['booking_container_id' => 1]],
            ['booking/done_unloading', ['booking_container_id' => 1]],
            ['booking/send_notes', ['booking_container_id' => 1, 'notes' => 'late']],
            ['booking/save_specification_booking_yard', ['booking_id' => 1]],
            ['booking/save_loading_booking_container', ['booking_container_id' => 1]],
            ['booking/save_unloading_booking_sail', ['booking_container_id' => 1]],
            ['booking/send_car_papers', ['booking_container_id' => 1]],
            ['make_reservation_expenses', ['booking_id' => 1]],
            ['make_general_expenses', ['booking_container_id' => 1]],
            ['update_expense', ['id' => $expense->id, 'value' => 120, 'version' => 1]],
            ['delete_expense', ['id' => $expense->id, 'version' => 1]],
            ['create_delivery_policy', ['booking_container_ids' => [999, 1]]],
            ['update_delivery_policy', ['id' => 1, 'booking_container_ids' => [999]]],
            ['delete_delivery_policy', ['id' => 1]],
            ['settle_delivery_policy', ['delivery_policy_id' => 1]],
            ['make_car_expenses', ['delivery_policy_id' => 1]],
            ['delivery_policy_details', ['delivery_policy_id' => 1]],
        ];
        foreach ($requests as [$path, $data]) {
            $this->postJson('/api/agent/'.$path, $data)->assertStatus(409)->assertJsonPath('status', false);
        }
        $this->assertSame(1, AgentExpense::count());
        $this->assertSame(100.0, (float) $expense->fresh()->value);
        $this->assertSame(900.0, (float) Agent::find(1)->wallet);
        $this->assertSame(0, DB::table('notes')->count());
        $this->assertSame(0, AgentExpense::visibleToAgent()->count());
        $this->assertConflict(fn () => $this->receipt('after-invoice'));
        $this->assertConflict(fn () => app(ContainerStageService::class)->complete(1, 1, 1));
    }

    public function test_booking_search_cannot_restore_an_invoiced_or_unassigned_order(): void
    {
        Schema::table('booking_containers', fn (Blueprint $t) => $t->string('container_no')->nullable());
        BookingContainer::find(1)->update(['container_no' => 'MATCH']);
        DB::table('bookings')->insert(['id' => 2, 'booking_number' => 'MATCH']);
        DB::table('invoices')->insert(['booking_id' => 1]);
        $this->actingAs(Agent::find(1), 'agent');
        $this->postJson('/api/agent/booking/fetch_bookings', ['word' => 'MATCH'])
            ->assertOk()->assertJsonPath('data', []);
    }

    private function assertConflict(callable $callback, int $status = 409): void
    {
        try {
            $callback();
            $this->fail('Expected a rejected operation');
        } catch (HttpException $e) {
            $this->assertSame($status, $e->getStatusCode());
        }
    }

    public function test_two_agents_keep_independent_assignments_and_late_receipts(): void
    {
        $service = app(ContainerStageService::class);
        $service->complete(1, 1, 1);
        $service->approve(1, 1);
        $service->assign(1, [2], 2);
        $this->travel(3)->days();
        // بعد اعتماد التحميل تختفي الحاوية من قائمة مندوب التحميل فورًا.
        $this->assertSame([], $service->visibleContainers(1, 1)->pluck('id')->all());
        $this->assertSame([1], $service->visibleContainers(2, 2)->pluck('id')->all());
        $this->assertCount(0, $service->visibleContainers(2, 1)->get());
        $service->complete(1, 2, 2);
        $this->receipt();
        $this->assertSame(3, (int) BookingContainer::find(1)->status);
        $this->assertSame(900.0, (float) Agent::find(1)->wallet);
        $this->assertSame(1000.0, (float) Agent::find(2)->wallet);
        $this->assertFalse($service->complete(1, 1, 1));
        $this->assertSame(3, (int) BookingContainer::find(1)->status);
    }

    public function test_same_agent_can_work_both_phases_without_duplicate_containers(): void
    {
        $service = app(ContainerStageService::class);
        $service->approve(1, 1);
        $service->assign(1, [1, 1], 2);
        $service->assign(1, [1], 2);
        $this->assertSame(0, $service->visibleContainers(1, 1)->count());
        $this->assertSame(1, $service->visibleContainers(1, 2)->count());
        $this->receipt('loading');
        $this->receipt('unloading', 1, 2);
        $this->assertSame(800.0, (float) Agent::find(1)->wallet);
    }

    public function test_close_checks_the_reviewed_version_and_rejects_late_writes(): void
    {
        $service = app(ContainerStageService::class);
        $service->approve(1, 1);
        $service->assign(1, [2], 2);
        $expense = $this->receipt();
        $this->assertConflict(fn () => $service->closeReceipts(1, 1, 7, 1));
        $service->closeReceipts(1, 1, 7, 2);
        $this->assertCount(0, $service->visibleContainers(1, 1)->get());
        $this->assertCount(1, $service->visibleContainers(2, 2)->get());
        $this->assertConflict(fn () => $this->receipt('late'));
        $this->assertConflict(fn () => app(StageExpenseService::class)->change(1, $expense->id, 1, ['value' => 120]));
        $this->assertConflict(fn () => app(StageExpenseService::class)->change(1, $expense->id, 1, null));
        $this->assertSame(1, AgentExpense::count());
        $this->assertSame(900.0, (float) Agent::find(1)->wallet);
    }

    public function test_retries_are_idempotent_even_after_closure(): void
    {
        $first = $this->receipt();
        $this->assertSame($first->id, $this->receipt()->id);
        $this->assertConflict(fn () => $this->receipt('receipt-1', 1, 1, 150));
        app(ContainerStageService::class)->approve(1, 1);
        app(ContainerStageService::class)->closeReceipts(1, 1, 7, 2);
        $this->assertSame($first->id, $this->receipt()->id);
        $this->assertSame(900.0, (float) Agent::find(1)->wallet);
    }

    public function test_stale_edits_and_deletes_do_not_overwrite_or_refund_twice(): void
    {
        $expense = $this->receipt();
        $service = app(StageExpenseService::class);
        $service->change(1, $expense->id, 1, ['value' => 120]);
        $this->assertConflict(fn () => $service->change(1, $expense->id, 1, ['value' => 130]));
        $this->assertConflict(fn () => $service->change(1, $expense->id, 1, null));
        $service->change(1, $expense->id, 2, null);
        $this->assertConflict(fn () => $service->change(1, $expense->id, 2, null));
        $this->receipt();
        $this->assertSame(1000.0, (float) Agent::find(1)->wallet);
        $this->assertSame(0, AgentExpense::count());
        $this->assertSame(1, AgentExpense::withTrashed()->count());
    }

    public function test_unassigned_agent_and_premature_unloading_are_rejected(): void
    {
        $this->assertConflict(fn () => $this->receipt('outsider', 2), 403);
        $service = app(ContainerStageService::class);
        $this->assertConflict(fn () => $service->complete(1, 1, 2), 403);
        $this->assertConflict(fn () => $service->assign(1, [2], 2));
        $this->assertConflict(fn () => $service->closeReceipts(1, 2, 7, 1));
        $this->assertSame(0, AgentExpense::count());
    }

    public function test_failed_image_write_rolls_back_receipt_and_wallet(): void
    {
        try {
            app(StageExpenseService::class)->create(1, ['booking_container_id' => 1, 'type_id' => 1, 'value' => 100], 'failed', str_repeat('a', 64), function () {
                throw new \RuntimeException('upload failed');
            });
            $this->fail('Expected failure');
        } catch (\RuntimeException $e) {
            $this->assertSame('upload failed', $e->getMessage());
        }
        $this->assertSame(0, AgentExpense::count());
        $this->assertSame(1000.0, (float) Agent::find(1)->wallet);
        $this->assertSame(0, BookingContainerStage::count());
    }

    public function test_assignment_api_serializes_the_selected_phase_after_three_days(): void
    {
        $service = app(ContainerStageService::class);
        $service->approve(1, 1);
        $service->assign(1, [1], 2);
        $this->travel(3)->days();
        $this->actingAs(Agent::find(1), 'agent');
        // اعتماد التحميل يخفي قائمة التحميل؛ التعتيق يبقى ظاهرًا.
        $this->getJson('/api/agent/booking/fetch_loading_assignments')->assertOk()
            ->assertJsonPath('data', []);
        $this->getJson('/api/agent/booking/fetch_unloading_assignments')->assertOk()
            ->assertJsonPath('data.0.booking_containers.0.stage_type', 'unloading')
            ->assertJsonPath('data.0.booking_containers.0.can_complete_stage', true);
        $this->getJson('/api/agent/booking/fetch_home_statistics')->assertOk()
            ->assertJsonPath('data.loading_assignments.daily_loading_assignments_count', 0)
            ->assertJsonPath('data.unloading_assignments.daily_unloading_assignments_count', 1);
    }

    public function test_expense_api_requires_retry_key_and_version_and_preserves_context(): void
    {
        $this->actingAs(Agent::find(1), 'agent');
        $data = ['value' => 100, 'service_id' => 1, 'booking_container_id' => 1, 'type_id' => 1];
        $this->postJson('/api/agent/make_general_expenses', $data)->assertStatus(422);
        $data['request_key'] = 'phone-request-1';
        $first = $this->postJson('/api/agent/make_general_expenses', $data)->assertOk()->assertJsonPath('data.version', 1);
        $id = $first->json('data.id');
        $this->postJson('/api/agent/make_general_expenses', $data)->assertOk()->assertJsonPath('data.id', $id);
        $this->postJson('/api/agent/update_expense', ['id' => $id, 'value' => 120])->assertStatus(422);
        $this->postJson('/api/agent/update_expense', ['id' => $id, 'value' => 120, 'version' => 1, 'type_id' => 2])->assertStatus(422);
        $this->postJson('/api/agent/update_expense', ['id' => $id, 'value' => 120, 'version' => 1])->assertOk()->assertJsonPath('data.version', 2);
        $this->postJson('/api/agent/delete_expense', ['id' => $id, 'version' => 1])->assertStatus(409);
        $this->assertSame(880.0, (float) Agent::find(1)->wallet);
        $this->assertSame(1, DB::table('log_activities')->count());
    }

    public function test_waiting_toggle_cannot_hide_completed_loading(): void
    {
        app(ContainerStageService::class)->complete(1, 1, 1);
        $this->assertConflict(fn () => app(ContainerStageService::class)->moveToLoading([1], false));
        $this->assertTrue((bool) BookingContainer::find(1)->is_in_loading);
    }

    public function test_rewind_is_rejected_once_the_next_agent_is_assigned(): void
    {
        $service = app(ContainerStageService::class);
        $service->approve(1, 1);
        $service->assign(1, [2], 2);
        $this->assertConflict(fn () => $service->rewind(1, 1));
        $this->assertSame(2, (int) BookingContainer::find(1)->status);
    }

    public function test_review_snapshot_starts_at_version_one(): void
    {
        DB::transaction(function () {
            $container = BookingContainer::lockForUpdate()->find(1);
            $this->assertSame(1, app(ContainerStageService::class)->receipts($container, 1)->version);
        });
    }

    public function test_superagent_closes_only_the_reviewed_phase(): void
    {
        $superagent = new \App\Models\Superagent;
        $superagent->forceFill(['id' => 7]);
        $this->actingAs($superagent, 'superagent');
        app(ContainerStageService::class)->approve(1, 1);
        $this->getJson('/api/superagent/booking/stage_receipts?booking_container_id=1&type_id=1')->assertOk()->assertJsonPath('data.receipts_version', 1);
        $this->receipt();
        $data = ['booking_container_id' => 1, 'type_id' => 1, 'receipts_version' => 1];
        $this->postJson('/api/superagent/booking/close_stage_receipts', $data)->assertStatus(409);
        $data['receipts_version'] = 2;
        $this->postJson('/api/superagent/booking/close_stage_receipts', $data)->assertOk()->assertJsonPath('data.receipts_closed_by', 7);
        $this->assertSame(0, app(ContainerStageService::class)->visibleContainers(1, 1)->count());
    }

    public function test_superagent_can_close_loading_receipts_without_moving_to_unloading(): void
    {
        $superagent = new \App\Models\Superagent;
        $superagent->forceFill(['id' => 7]);
        $this->actingAs($superagent, 'superagent');
        $this->receipt();
        $before = BookingContainer::find(1)->getAttributes();

        $this->getJson('/api/superagent/booking/pending_stage_receipts?type_id=1')
            ->assertOk()->assertJsonPath('data.data.0.booking_container_id', 1);
        $data = ['booking_container_id' => 1, 'type_id' => 1, 'receipts_version' => 1];
        $this->postJson('/api/superagent/booking/close_stage_receipts', $data)->assertStatus(409);
        $data['receipts_version'] = 2;
        $this->postJson('/api/superagent/booking/close_stage_receipts', $data)
            ->assertOk()->assertJsonPath('data.receipts_closed_by', 7);
        $this->postJson('/api/superagent/booking/close_stage_receipts', $data)->assertOk();
        $this->assertSame($before, BookingContainer::find(1)->getAttributes());
        $this->assertSame(1, app(ContainerStageService::class)->currentType(BookingContainer::find(1)));
        $this->getJson('/api/superagent/booking/pending_stage_receipts?type_id=1')
            ->assertOk()->assertJsonPath('data.data', []);
        $this->assertConflict(fn () => $this->receipt('after-close'));

        $this->assertTrue(app(ContainerStageService::class)->approve(1, 1, 7));
        $this->assertSame(2, app(ContainerStageService::class)->currentType(BookingContainer::find(1)));
    }

    public function test_loading_receipts_cannot_close_while_waiting_for_loading(): void
    {
        BookingContainer::find(1)->update(['is_in_loading' => false]);
        $this->assertConflict(fn () => app(ContainerStageService::class)->closeReceipts(1, 1, 7, 1));
        $this->assertSame(0, BookingContainerStage::count());
    }

    /** @dataProvider loadingReceiptStates */
    public function test_loading_approval_keeps_container_visible_for_unloading_assignment(bool $closeReceipts): void
    {
        Schema::table('booking_containers', fn (Blueprint $t) => $t->date('arrival_date')->nullable());
        Schema::table('delivery_policy_containers', fn (Blueprint $t) => $t->timestamps());
        $service = app(ContainerStageService::class);
        $service->complete(1, 1, 1);
        if ($closeReceipts) {
            $service->closeReceipts(1, 1, 7, 1);
        }
        $service->approve(1, 1, 7);
        $this->assertNull(BookingContainer::find(1)->unloading_completed_at);
        $this->assertFalse($service->assignments(1, 2)->exists());

        $superagent = new \App\Models\Superagent;
        $superagent->forceFill(['id' => 7]);
        $this->actingAs($superagent, 'superagent');
        $this->getJson('/api/superagent/booking/loading')
            ->assertOk()->assertJsonPath('data.data', []);
        $unloading = $this->getJson('/api/superagent/booking/unloading');
        $this->assertSame(200, $unloading->status(), $unloading->getContent());
        $unloading->assertJsonPath('data.data.0.booking_containers.0.id', 1);
        foreach (['', '?stage_type=unloading'] as $query) {
            $this->getJson('/api/superagent/booking/missions/all'.$query)
                ->assertOk()->assertJsonPath('data.data.0.id', 1)
                ->assertJsonPath('data.data.0.booking_containers.0.type', 'unloading');
        }

        // بعد اعتماد التحميل تختفي قائمة التحميل لدى المندوب؛ تكليف التعتيق منفصل.
        $this->actingAs(Agent::find(1), 'agent');
        $this->getJson('/api/agent/booking/fetch_loading_assignments')->assertOk()
            ->assertJsonPath('data', []);
        $service->assign(1, [2], 2);
        $this->actingAs(Agent::find(2), 'agent');
        $this->getJson('/api/agent/booking/fetch_unloading_assignments')
            ->assertOk()->assertJsonPath('data.0.booking_containers.0.id', 1);
    }

    public static function loadingReceiptStates(): array
    {
        return ['open receipts' => [false], 'closed receipts' => [true]];
    }

    public function test_approved_expenses_cannot_be_edited_by_the_agent(): void
    {
        $expense = $this->receipt();
        $expense->update(['admin_approval' => 1]);
        $this->assertConflict(fn () => app(StageExpenseService::class)->change(1, $expense->id, 1, ['value' => 50]));
        $this->assertSame(900.0, (float) Agent::find(1)->wallet);
    }

    public function test_migration_preserves_legacy_rows_without_inventing_past_assignments(): void
    {
        $migration = require database_path('migrations/2026_09_13_000001_add_parallel_container_stages.php');
        $migration->down();
        DB::table('booking_container_agents')->insert([
            'booking_container_id' => 1, 'agent_id' => 2, 'booking_container_status' => 3,
            'superagent_specification_approved' => 1, 'superagent_loading_approved' => 1,
        ]);
        $migration->up();
        $this->assertSame(2, DB::table('booking_container_agents')->count());
        $this->assertSame(1, (int) DB::table('booking_container_agents')->where('agent_id', 1)->value('stage_type'));
        $this->assertSame(2, (int) DB::table('booking_container_agents')->where('agent_id', 2)->value('stage_type'));
    }

    public static function legacyStates(): array
    {
        return [
            'specification in progress' => [0, 0, 0, 0, 0, 0],
            'specification awaiting approval' => [1, 0, 0, 0, 0, 0],
            'waiting for loading' => [1, 1, 0, 0, 0, 1],
            'loading in progress' => [1, 1, 0, 0, 1, 1],
            'loading awaiting approval' => [2, 1, 0, 0, 1, 1],
            'unloading in progress' => [2, 1, 1, 0, 1, 2],
            'unloading awaiting approval' => [3, 1, 1, 0, 1, 2],
            'fully approved' => [3, 1, 1, 1, 1, 2],
        ];
    }

    /** @dataProvider legacyStates */
    public function test_migration_preserves_each_existing_state(int $status, int $specification, int $loading, int $unloading, int $inLoading, int $expectedType): void
    {
        $migration = require database_path('migrations/2026_09_13_000001_add_parallel_container_stages.php');
        $migration->down();
        $values = ['superagent_specification_approved' => $specification, 'superagent_loading_approved' => $loading, 'superagent_unloading_approved' => $unloading, 'is_in_loading' => $inLoading];
        DB::table('booking_containers')->where('id', 1)->update($values + ['status' => $status]);
        DB::table('booking_container_agents')->where('agent_id', 1)->update($values + ['booking_container_status' => $status]);
        DB::table('agent_expenses')->insert(['agent_id' => 1, 'booking_container_id' => 1, 'booking_id' => 1, 'type_id' => $expectedType, 'value' => 75]);
        $before = DB::table('booking_containers')->where('id', 1)->first();
        $migration->up();
        $this->assertEquals($before, DB::table('booking_containers')->where('id', 1)->first());
        $this->assertSame($expectedType, (int) DB::table('booking_container_agents')->where('agent_id', 1)->value('stage_type'));
        $this->assertSame(1, DB::table('booking_container_agents')->count());
        $this->assertSame(0, BookingContainerStage::count());
        $this->assertSame(75.0, (float) AgentExpense::first()->value);
        $this->assertSame(1, AgentExpense::first()->version);
        $this->assertNull(AgentExpense::first()->request_key);
        $this->assertSame(1000.0, (float) Agent::find(1)->wallet);
    }

    public function test_existing_stage_is_created_once_when_receipts_are_reviewed(): void
    {
        $this->assertSame(0, BookingContainerStage::count());
        $service = app(ContainerStageService::class);
        $this->assertSame(1, $service->visibleContainers(1, 1)->count());
        $this->assertSame(0, BookingContainerStage::count());
        DB::transaction(function () use ($service) {
            $container = BookingContainer::lockForUpdate()->findOrFail(1);
            $first = $service->receipts($container, 1);
            $second = $service->receipts($container, 1);
            $this->assertSame($first->id, $second->id);
            $this->assertNull($first->receipts_closed_at);
        });
        $this->assertSame(1, BookingContainerStage::count());
        $this->assertSame(1, (int) BookingContainer::find(1)->status);
    }

    public function test_old_completed_stage_without_timestamp_is_not_completed_again(): void
    {
        BookingContainer::find(1)->update(['status' => 2, 'loading_completed_at' => null]);
        $this->assertFalse(app(ContainerStageService::class)->complete(1, 1, 1));
        $this->assertSame(2, (int) BookingContainer::find(1)->status);
    }

    public function test_insufficient_balance_rolls_back_every_database_write(): void
    {
        $this->assertConflict(fn () => $this->receipt('too-expensive', 1, 1, 1001), 422);
        $this->assertSame(0, AgentExpense::count());
        $this->assertSame(0, BookingContainerStage::count());
        $this->assertSame(1000.0, (float) Agent::find(1)->wallet);
    }

    public function test_admin_cannot_change_a_receipt_after_stage_closure(): void
    {
        $expense = $this->receipt();
        $service = app(ContainerStageService::class);
        $service->approve(1, 1);
        $service->closeReceipts(1, 1, 7, 2);
        $this->assertConflict(fn () => app(StageExpenseService::class)->change(1, $expense->id, 1, ['value' => 200], null, true));
        $this->assertConflict(fn () => app(StageExpenseService::class)->change(1, $expense->id, 1, null, null, true));
        $this->assertSame(100.0, (float) $expense->fresh()->value);
        $this->assertSame(900.0, (float) Agent::find(1)->wallet);
    }

    public function test_used_migration_cannot_discard_receipt_history(): void
    {
        $this->receipt();
        $migration = require database_path('migrations/2026_09_13_000001_add_parallel_container_stages.php');
        try {
            $migration->down();
            $this->fail('Used migration must not be rolled back');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('Reconcile data', $e->getMessage());
        }
        $this->assertTrue(Schema::hasTable('booking_container_stages'));
        $this->assertTrue(Schema::hasColumn('agent_expenses', 'request_key'));
        $this->assertSame(1, AgentExpense::count());
    }
}
