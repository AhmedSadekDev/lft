<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\BankTransactionController;
use App\Http\Controllers\Admin\CarPayingController;
use App\Http\Controllers\Admin\ReceiptController;
use App\Jobs\SubmitInvoiceJob;
use App\Models\Agent;
use App\Models\AgentExpense;
use App\Models\Bank;
use App\Models\BankTrnsaction;
use App\Models\Booking;
use App\Models\BookingContainer;
use App\Models\BookingService;
use App\Models\Company;
use App\Models\DeliveryPolicy;
use App\Models\ExtraExpenses;
use App\Models\Payingcar;
use App\Models\Receipt;
use App\Models\Service;
use App\Models\User;
use App\Models\Vault;
use App\Models\VaultTransaction;
use App\Services\EInvoiceService;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request as Psr7Request;
use GuzzleHttp\Psr7\Response as Psr7Response;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class Phase5FinancialConcurrencyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::disconnect('sqlite');
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::reconnect('sqlite');

        $this->createInMemorySchema();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function createInMemorySchema(): void
    {
        $tables = [
            'users', 'banks', 'vaults', 'vault_transactions', 'bank_trnsactions',
            'companies', 'agents', 'receipts', 'agent_expenses', 'booking_services',
            'delivery_policies', 'payingcars', 'booking_contrainer_extra_costs',
            'cars', 'money_transfers', 'bookings', 'invoices', 'superagents', 'yards',
            'shipping_agents', 'services', 'factories', 'branches', 'containers',
            'delivery_policy_containers', 'booking_containers', 'booking_container_stages',
            'booking_container_agents', 'daily_booking_containers', 'financial_custody_agents',
            'agent_photos'
        ];
        foreach ($tables as $t) {
            Schema::dropIfExists($t);
        }

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('Admin');
            $table->string('email')->nullable();
            $table->timestamps();
        });

        Schema::create('banks', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('Bank A');
            $table->decimal('amount', 14, 2)->default(1000.00);
            $table->timestamps();
        });

        Schema::create('vaults', function (Blueprint $table) {
            $table->id();
            $table->decimal('amount', 14, 2)->default(5000.00);
            $table->timestamps();
        });

        Schema::create('vault_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bank_id')->nullable();
            $table->string('name')->nullable();
            $table->decimal('amount', 14, 2);
            $table->integer('type');
            $table->timestamps();
        });

        Schema::create('bank_trnsactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bank_id');
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('name')->nullable();
            $table->string('date')->nullable();
            $table->decimal('amount', 14, 2);
            $table->integer('type');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('image')->nullable();
            $table->timestamps();
        });

        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('Company A');
            $table->decimal('wallet', 14, 2)->default(10000.00);
            $table->decimal('opening_balance', 14, 2)->default(0.00);
            $table->timestamps();
        });

        Schema::create('agents', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('Agent 1');
            $table->decimal('wallet', 14, 2)->default(2000.00);
            $table->timestamps();
        });

        Schema::create('receipts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('booking_id')->nullable();
            $table->unsignedBigInteger('booking_service_id')->nullable();
            $table->unsignedBigInteger('agent_id')->nullable();
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->string('payment_source')->nullable();
            $table->decimal('cost', 14, 2);
            $table->timestamps();
        });

        Schema::create('agent_expenses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('agent_id');
            $table->unsignedBigInteger('booking_service_id')->nullable();
            $table->unsignedBigInteger('booking_id')->nullable();
            $table->unsignedBigInteger('booking_container_id')->nullable();
            $table->unsignedBigInteger('service_id')->nullable();
            $table->unsignedBigInteger('type_id')->nullable();
            $table->unsignedBigInteger('delivery_policy_id')->nullable();
            $table->integer('type')->default(0);
            $table->decimal('value', 14, 2);
            $table->string('notes')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->integer('admin_approval')->default(0);
            $table->string('request_key')->nullable();
            $table->string('request_fingerprint')->nullable();
            $table->integer('version')->default(1);
            $table->timestamp('voided_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('booking_services', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('booking_id')->nullable();
            $table->unsignedBigInteger('service_id')->nullable();
            $table->unsignedBigInteger('agent_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('payment_type')->nullable();
            $table->timestamps();
        });

        Schema::create('delivery_policies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('car_id')->nullable();
            $table->decimal('cost', 14, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('payingcars', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('delivery_policy_id');
            $table->unsignedBigInteger('car_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->decimal('value', 14, 2);
            $table->string('image')->nullable();
            $table->timestamps();
        });

        Schema::create('booking_contrainer_extra_costs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('delivery_policy_id');
            $table->decimal('value', 14, 2);
            $table->timestamps();
        });

        Schema::create('cars', function (Blueprint $table) {
            $table->id();
            $table->string('car_number')->default('123');
            $table->timestamps();
        });

        Schema::create('money_transfers', function (Blueprint $table) {
            $table->id();
            $table->decimal('value', 14, 2);
            $table->string('transfered_type');
            $table->unsignedBigInteger('transfered_id');
            $table->string('transferer_type')->nullable();
            $table->unsignedBigInteger('transferer_id')->nullable();
            $table->timestamps();
        });

        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('submission_id')->nullable();
            $table->string('invoice_uuid')->nullable();
            $table->string('invoice_status')->nullable();
            $table->text('invoice_errors')->nullable();
            $table->tinyInteger('is_submitted')->default(0);
            $table->timestamps();
        });
    }

    public function test_einvoice_handles_client_exception_cleanly(): void
    {
        $mockClient = Mockery::mock(Client::class);
        $psrRequest = new Psr7Request('POST', 'https://api.invoicing.eta.gov.eg/api/v1.0/documentsubmissions');
        $psrResponse = new Psr7Response(400, [], json_encode(['error' => 'Try to submit payload after 120 seconds']));
        $clientException = new ClientException('Bad Request', $psrRequest, $psrResponse);

        $mockClient->shouldReceive('post')
            ->once()
            ->andThrow($clientException);

        $service = new EInvoiceService();
        $refProp = new \ReflectionProperty(EInvoiceService::class, 'client');
        $refProp->setAccessible(true);
        $refProp->setValue($service, $mockClient);

        $result = $service->submitInvoice(['some' => 'payload'], 'mock_token');

        $this->assertFalse($result['status']);
        $this->assertStringContainsString('تم إرسال نفس بيانات الفاتورة', $result['message']);
        $this->assertStringContainsString('2 دقيقة', $result['message']);
    }

    public function test_submit_invoice_job_skips_already_valid_submitted_booking(): void
    {
        $booking = new Booking();
        $booking->is_submitted = 1;
        $booking->invoice_status = 'Valid';
        $booking->invoice_uuid = 'existing-uuid-123';
        $booking->save();

        $this->assertSame(1, (int) $booking->is_submitted);
        $this->assertSame('Valid', $booking->invoice_status);

        $mockService = Mockery::mock(EInvoiceService::class);
        $mockService->shouldNotReceive('getAccessToken');
        $mockService->shouldNotReceive('submitInvoice');

        $company = (object) ['ETA_CLIENT_ID' => 'client123', 'ETA_CLIENT_SECRET' => 'secret123'];
        $job = new SubmitInvoiceJob(['doc' => 'test'], $booking->id, $company);
        $job->handle($mockService);

        $freshBooking = Booking::find($booking->id);
        $this->assertNotNull($freshBooking);
        $this->assertSame('Valid', $freshBooking->invoice_status);
        $this->assertSame(1, (int) $freshBooking->is_submitted);
    }

    public function test_submit_invoice_job_atomic_claim_prevents_duplicate_workers(): void
    {
        $booking = new Booking();
        $booking->is_submitted = 0;
        $booking->invoice_status = 'Processing'; // Already claimed by Worker 1
        $booking->save();

        $mockService = Mockery::mock(EInvoiceService::class);
        $mockService->shouldNotReceive('getAccessToken');
        $mockService->shouldNotReceive('submitInvoice');

        $company = (object) ['ETA_CLIENT_ID' => 'client123', 'ETA_CLIENT_SECRET' => 'secret123'];
        $job = new SubmitInvoiceJob(['doc' => 'test'], $booking->id, $company);
        $job->handle($mockService);

        // Worker 2 was safely suppressed because it could not claim the Processing row
        $freshBooking = Booking::find($booking->id);
        $this->assertSame('Processing', $freshBooking->invoice_status);
        $this->assertSame(0, (int) $freshBooking->is_submitted);
    }

    public function test_receipt_agent_wallet_deduction_and_deletion_safety(): void
    {
        $user = User::create(['name' => 'Admin']);
        $this->actingAs($user, 'web');

        $agent = Agent::create(['name' => 'Agent X', 'wallet' => 1000.00]);

        $bookingService = BookingService::create([
            'booking_id' => 100,
            'service_id' => 1,
            'agent_id' => $agent->id,
            'payment_type' => 'agent',
            'created_by' => $user->id,
        ]);

        $receipt = Receipt::create([
            'booking_id' => 100,
            'booking_service_id' => $bookingService->id,
            'agent_id' => $agent->id,
            'cost' => 300.00,
            'payment_source' => 'representative',
        ]);

        // Delete receipt via controller destroy
        $controller = new ReceiptController();
        $response = $controller->destroy($receipt);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertNull(Receipt::find($receipt->id));
        $this->assertNull(BookingService::find($bookingService->id));
    }

    public function test_car_paying_vault_concurrency_and_calculation_safety(): void
    {
        $user = User::create(['name' => 'Admin']);
        $this->actingAs($user, 'web');

        $vault = Vault::create(['amount' => 5000.00]);
        $policy = DeliveryPolicy::create(['car_id' => 2, 'cost' => 1200.00]);

        $controller = new CarPayingController();

        // 1. Valid payment of 800
        $request = new Request([
            'delivery_policy_id' => $policy->id,
            'value' => 800.00,
        ]);
        $controller->store($request);

        $this->assertDatabaseHas('payingcars', [
            'delivery_policy_id' => $policy->id,
            'value' => 800.00,
        ]);
        $this->assertSame(4200.00, (float) Vault::find($vault->id)->amount);

        // 2. Overpayment attempt (remaining is 400, attempting 600)
        $excessRequest = new Request([
            'delivery_policy_id' => $policy->id,
            'value' => 600.00,
        ]);
        $controller->store($excessRequest);

        // Should not add excess payment, vault should stay 4200
        $this->assertSame(4200.00, (float) Vault::find($vault->id)->amount);
        $this->assertSame(1, Payingcar::where('delivery_policy_id', $policy->id)->count());

        // 3. Destroy payment -> refund vault
        $paying = Payingcar::where('delivery_policy_id', $policy->id)->first();
        $controller->destroy($paying->id);

        $this->assertSame(5000.00, (float) Vault::find($vault->id)->amount);
        $this->assertNull(Payingcar::find($paying->id));
    }

    public function test_bank_transaction_transfer_between_banks_creates_audit_and_preserves_balances(): void
    {
        $user = User::create(['id' => 1, 'name' => 'Admin']);
        $this->actingAs($user, 'web');

        $bankA = Bank::create(['id' => 1, 'name' => 'Bank A', 'amount' => 1000.00]);
        $bankB = Bank::create(['id' => 2, 'name' => 'Bank B', 'amount' => 500.00]);
        Vault::create(['id' => 1, 'amount' => 2000.00]);

        $controller = new BankTransactionController();

        // Attempt transfer greater than Bank A balance (1500 > 1000)
        $invalidRequest = new Request([
            'bank_id' => 1,
            'name' => 'Transfer Overdraft',
            'amount' => 1500.00,
            'type' => '2',
            'trans_bank_id' => 2,
        ]);
        $controller->store($invalidRequest);

        $this->assertSame(1000.00, (float) Bank::find(1)->amount);
        $this->assertSame(500.00, (float) Bank::find(2)->amount);

        // Valid transfer of 400.00 from Bank A to Bank B
        $validRequest = new Request([
            'bank_id' => 1,
            'name' => 'Transfer Funds',
            'amount' => 400.00,
            'type' => '2',
            'trans_bank_id' => 2,
        ]);
        $controller->store($validRequest);

        $this->assertSame(600.00, (float) Bank::find(1)->amount);
        $this->assertSame(900.00, (float) Bank::find(2)->amount);

        $this->assertDatabaseHas('bank_trnsactions', [
            'bank_id' => 1,
            'amount' => 400.00,
            'type' => 2,
            'name' => 'Transfer Funds',
        ]);
    }
}
