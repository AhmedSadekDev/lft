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

    private function receipt(string $key = 'receipt-1', int $agent = 1, int $type = 1, int $amount = 100): AgentExpense
    {
        return app(StageExpenseService::class)->create($agent, ['booking_container_id' => 1, 'type_id' => $type, 'value' => $amount], $key, hash('sha256', "$type:$amount"), fn () => null);
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
        $this->assertSame([1], $service->visibleContainers(1, 1)->pluck('id')->all());
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
        $this->assertSame(1, $service->visibleContainers(1, 1)->count());
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
        $this->assertConflict(fn () => $service->closeReceipts(1, 1, 7, 1));
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
        $this->getJson('/api/agent/booking/fetch_loading_assignments')->assertOk()
            ->assertJsonPath('data.0.booking_containers.0.stage_type', 'loading')
            ->assertJsonPath('data.0.booking_containers.0.operational_stage', 'unloading')
            ->assertJsonPath('data.0.booking_containers.0.can_upload_receipts', true)
            ->assertJsonPath('data.0.booking_containers.0.can_complete_stage', false);
        $this->getJson('/api/agent/booking/fetch_unloading_assignments')->assertOk()
            ->assertJsonPath('data.0.booking_containers.0.stage_type', 'unloading')
            ->assertJsonPath('data.0.booking_containers.0.can_complete_stage', true);
        $this->getJson('/api/agent/booking/fetch_home_statistics')->assertOk()
            ->assertJsonPath('data.loading_assignments.daily_loading_assignments_count', 1)
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
