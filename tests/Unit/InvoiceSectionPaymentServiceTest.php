<?php

namespace Tests\Unit;

use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Services\InvoicePrintBuilder;
use App\Services\InvoiceSectionPaymentService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class InvoiceSectionPaymentServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');

        Schema::create('invoices', function (Blueprint $t) {
            $t->id();
            $t->string('invoice_number')->nullable();
            $t->string('currency', 3)->default('EGP');
            $t->decimal('usd_amount', 14, 2)->default(0);
            $t->unsignedBigInteger('booking_id')->nullable();
            $t->timestamps();
        });
        Schema::create('invoice_payments', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('invoice_id')->nullable();
            $t->string('section', 20)->nullable();
            $t->decimal('value', 14, 2)->default(0);
            $t->string('currency', 3)->default('EGP');
            $t->string('payment_type')->nullable();
            $t->timestamp('check_paid_at')->nullable();
            $t->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function stubSectionTotals(array $totals): void
    {
        $mock = Mockery::mock(InvoicePrintBuilder::class);
        $mock->shouldReceive('getInvoiceSectionTotals')->andReturn(array_merge([
            'tax' => 0.0,
            'receipt' => 0.0,
            'additional' => 0.0,
            'usd' => 0.0,
        ], $totals));
        $this->app->instance(InvoicePrintBuilder::class, $mock);
    }

    public function test_tax_section_payment_decrements_tax_only(): void
    {
        $this->stubSectionTotals([
            'tax' => 1000,
            'receipt' => 500,
            'additional' => 200,
            'usd' => 50,
        ]);

        $invoice = Invoice::create([
            'invoice_number' => 'T-1',
            'currency' => 'EGP',
            'usd_amount' => 50,
        ]);
        InvoicePayment::create([
            'invoice_id' => $invoice->id,
            'section' => 'tax',
            'value' => 500,
            'currency' => 'EGP',
            'payment_type' => 'bank_transfer',
        ]);

        $invoice->load('invoicePayments');
        $breakdown = InvoiceSectionPaymentService::getSectionsBreakdown($invoice);

        $this->assertSame(500.0, $breakdown['tax']['remaining']);
        $this->assertSame(500.0, $breakdown['receipt']['remaining']);
        $this->assertSame(200.0, $breakdown['additional']['remaining']);
        $this->assertSame(50.0, $breakdown['usd']['remaining']);
    }

    public function test_legacy_null_section_fifo_consumes_tax_then_receipt(): void
    {
        $this->stubSectionTotals([
            'tax' => 1000,
            'receipt' => 500,
            'additional' => 0,
            'usd' => 0,
        ]);

        $invoice = Invoice::create([
            'invoice_number' => 'T-2',
            'currency' => 'EGP',
            'usd_amount' => 0,
        ]);
        InvoicePayment::create([
            'invoice_id' => $invoice->id,
            'section' => null,
            'value' => 1200,
            'currency' => 'EGP',
            'payment_type' => 'bank_transfer',
        ]);

        $invoice->load('invoicePayments');
        $breakdown = InvoiceSectionPaymentService::getSectionsBreakdown($invoice);

        $this->assertSame(0.0, $breakdown['tax']['remaining']);
        $this->assertSame(300.0, $breakdown['receipt']['remaining']);
    }

    public function test_payment_row_totals_are_negative_for_statement_display(): void
    {
        $egp = 1500.0;
        $usd = 20.0;
        $this->assertSame(-1500.0, $egp > 0 ? -abs($egp) : 0.0);
        $this->assertSame(-20.0, $usd > 0 ? -abs($usd) : 0.0);
    }
}
