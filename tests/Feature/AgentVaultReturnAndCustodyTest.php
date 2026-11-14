<?php

namespace Tests\Feature;

use App\Exports\AgentAccountExport;
use App\Exports\VaultTransactionExport;
use App\Http\Controllers\Admin\AgentController;
use App\Http\Controllers\Admin\ExpenseController;
use App\Http\Controllers\Admin\MoneyTransferController;
use App\Http\Requests\Admin\MoneyTransferRequest;
use App\Models\Agent;
use App\Models\AgentExpense;
use App\Models\MoneyTransfer;
use App\Models\User;
use App\Models\Vault;
use App\Models\VaultTransaction;
use App\Services\AgentVaultReturnService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AgentVaultReturnAndCustodyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');

        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name')->nullable();
            $t->timestamps();
        });
        Schema::create('agents', function (Blueprint $t) {
            $t->id();
            $t->string('name')->nullable();
            $t->string('wallet')->nullable();
            $t->string('total_wallet')->nullable();
            $t->string('device_token')->nullable();
            $t->timestamps();
        });
        Schema::create('banks', function (Blueprint $t) {
            $t->id();
            $t->string('name')->nullable();
            $t->string('amount')->nullable();
            $t->timestamps();
        });
        Schema::create('vaults', function (Blueprint $t) {
            $t->id();
            $t->string('amount')->nullable();
            $t->timestamps();
        });
        Schema::create('vault_transactions', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('agient_id')->nullable();
            $t->unsignedBigInteger('bank_id')->nullable();
            $t->string('name')->nullable();
            $t->string('amount')->nullable();
            $t->boolean('type');
            $t->timestamps();
        });
        Schema::create('money_transfers', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('transferer_id')->nullable();
            $t->string('transferer_type')->nullable();
            $t->string('value')->nullable();
            $t->integer('type')->nullable();
            $t->unsignedBigInteger('transfered_id')->nullable();
            $t->string('transfered_type')->nullable();
            $t->unsignedBigInteger('delivery_policy_id')->nullable();
            $t->text('notes')->nullable();
            $t->string('image')->nullable();
            $t->date('date')->nullable();
            $t->string('address')->nullable();
            $t->timestamps();
        });
        Schema::create('agent_expenses', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('agent_id')->nullable();
            $t->unsignedBigInteger('booking_id')->nullable();
            $t->unsignedBigInteger('booking_container_id')->nullable();
            $t->unsignedBigInteger('booking_service_id')->nullable();
            $t->unsignedBigInteger('service_id')->nullable();
            $t->unsignedBigInteger('delivery_policy_id')->nullable();
            $t->string('value')->nullable();
            $t->integer('type_id')->nullable();
            $t->integer('admin_approval')->nullable();
            $t->integer('version')->default(1);
            $t->timestamp('reviewed_at')->nullable();
            $t->timestamp('wallet_debited_at')->nullable();
            $t->timestamp('voided_at')->nullable();
            $t->string('image')->nullable();
            $t->timestamps();
        });
        Schema::create('bookings', function (Blueprint $t) {
            $t->id();
        });
        Schema::create('invoices', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('booking_id')->nullable();
        });
        Schema::create('booking_containers', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('booking_id')->nullable();
        });
        Schema::create('delivery_policies', function (Blueprint $t) {
            $t->id();
            $t->boolean('is_settled')->default(false);
        });
        Schema::create('delivery_policy_containers', function (Blueprint $t) {
            $t->unsignedBigInteger('delivery_policy_id');
            $t->unsignedBigInteger('booking_container_id');
        });
        Schema::create('app_notifications', function (Blueprint $t) {
            $t->id();
            $t->string('title')->nullable();
            $t->text('text')->nullable();
            $t->unsignedBigInteger('notificationable_id')->nullable();
            $t->string('notificationable_type')->nullable();
            $t->integer('type')->nullable();
            $t->unsignedBigInteger('booking_container_id')->nullable();
            $t->integer('type_id')->nullable();
            $t->boolean('is_read')->default(0);
            $t->timestamps();
        });

        DB::table('users')->insert(['id' => 1, 'name' => 'Admin']);
        DB::table('agents')->insert([
            'id' => 1,
            'name' => 'خالد غراب',
            'wallet' => '5000',
            'total_wallet' => '5000',
        ]);
        DB::table('vaults')->insert(['id' => 1, 'amount' => '100000']);
    }

    public function test_admin_custody_writes_agient_id_on_vault_transaction(): void
    {
        $this->actingAs(User::find(1), 'web');

        $request = MoneyTransferRequest::create('/financial_custody_agents', 'POST', [
            'value' => 10000,
            'agent_id' => 1,
        ]);
        $request->setContainer(app())->setRedirector(app('redirect'));
        $request->validateResolved();

        $response = (new MoneyTransferController())->store($request);

        $this->assertTrue($response->isRedirect());
        $this->assertSame(90000.0, (float) Vault::first()->amount);
        $this->assertSame(15000.0, (float) Agent::find(1)->wallet);

        $vt = VaultTransaction::first();
        $this->assertNotNull($vt);
        $this->assertSame(1, (int) $vt->agient_id);
        $this->assertStringContainsString('خالد غراب', (string) $vt->name);
        $this->assertSame('خالد غراب', $vt->agient?->name);

        $this->assertDatabaseHas('money_transfers', [
            'transfered_id' => 1,
            'transfered_type' => Agent::class,
            'type' => MoneyTransfer::fromDashboard,
            'value' => '10000',
        ]);
    }

    public function test_vault_return_stays_pending_until_approved(): void
    {
        $agent = Agent::find(1);
        $service = app(AgentVaultReturnService::class);

        $pending = $service->request($agent, 2000.0, 'إرجاع جزئي');

        $this->assertSame(MoneyTransfer::vaultReturnPending, (int) $pending->type);
        $this->assertSame(3000.0, (float) Agent::find(1)->wallet);
        $this->assertSame(100000.0, (float) Vault::first()->amount);
        $this->assertDatabaseCount('vault_transactions', 0);

        $service->approve($pending->id);

        $this->assertSame(102000.0, (float) Vault::first()->amount);
        $this->assertSame(3000.0, (float) Agent::find(1)->wallet);
        $this->assertSame(MoneyTransfer::vaultReturnApproved, (int) $pending->fresh()->type);
        $this->assertDatabaseHas('vault_transactions', [
            'agient_id' => 1,
            'amount' => '2000',
            'type' => 1,
        ]);
    }

    public function test_rejecting_vault_return_restores_agent_wallet(): void
    {
        $agent = Agent::find(1);
        $service = app(AgentVaultReturnService::class);
        $pending = $service->request($agent, 1500.0);

        $service->reject($pending->id);

        $this->assertSame(5000.0, (float) Agent::find(1)->wallet);
        $this->assertSame(100000.0, (float) Vault::first()->amount);
        $this->assertDatabaseCount('money_transfers', 0);
        $this->assertDatabaseCount('vault_transactions', 0);
    }

    public function test_agents_list_includes_pending_vault_returns_without_double_deducting_them(): void
    {
        $agent = Agent::find(1);
        app(AgentVaultReturnService::class)->request($agent, 2000.0);
        DB::table('agent_expenses')->insert([
            'agent_id' => 1,
            'value' => '500',
            'admin_approval' => 0,
            'version' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $viewData = app(AgentController::class)->index()->getData();

        $this->assertSame(3000.0, (float) Agent::find(1)->wallet);
        $this->assertSame(500.0, (float) $viewData['pendingExpensesByAgent'][1]);
        $this->assertSame(2000.0, (float) $viewData['pendingVaultReturnsByAgent'][1]);
        $this->assertSame(2500.0, (float) $viewData['underReviewByAgent'][1]);
    }

    public function test_agent_expenses_list_orders_pending_vault_returns_before_approved_returns(): void
    {
        $approved = MoneyTransfer::create([
            'value' => 1000,
            'type' => MoneyTransfer::vaultReturnApproved,
            'transferer_type' => Agent::class,
            'transferer_id' => 1,
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);
        $pending = MoneyTransfer::create([
            'value' => 2000,
            'type' => MoneyTransfer::vaultReturnPending,
            'transferer_type' => Agent::class,
            'transferer_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $view = app(ExpenseController::class)->agent_expenses(new Request(), Agent::findOrFail(1));
        $rows = $view->getData()['allExpenses'];

        $this->assertSame([$pending->id, $approved->id], $rows->pluck('id')->all());
    }

    public function test_agent_and_vault_excel_exports_build_rows(): void
    {
        VaultTransaction::create([
            'agient_id' => 1,
            'name' => 'صرف عهدة للمندوب: خالد غراب',
            'amount' => 10000,
            'type' => 0,
        ]);
        MoneyTransfer::create([
            'value' => 10000,
            'type' => MoneyTransfer::fromDashboard,
            'transferer_type' => User::class,
            'transferer_id' => 1,
            'transfered_type' => Agent::class,
            'transfered_id' => 1,
        ]);

        $agentRows = (new AgentAccountExport(Agent::find(1)))->collection();
        $this->assertGreaterThanOrEqual(1, $agentRows->count());
        $this->assertSame('عهدة واردة', $agentRows->first()['kind']);

        VaultTransaction::create([
            'agient_id' => 1,
            'name' => 'تحويل الي الخزنه: خالد غراب',
            'amount' => 2000,
            'type' => 1,
        ]);

        $vaultRows = (new VaultTransactionExport(null, Request::create('/')))->collection();
        $this->assertCount(2, $vaultRows);
        $this->assertTrue($vaultRows->contains(fn ($row) => $row['agent'] === 'خالد غراب'));
    }
}
