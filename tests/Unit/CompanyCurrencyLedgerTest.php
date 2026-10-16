<?php

namespace Tests\Unit;

use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Support\CompanyCurrencyLedger;
use App\Support\InvoiceCurrency;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CompanyCurrencyLedgerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('invoices', function (Blueprint $t) {
            $t->id();
            $t->string('currency', 3)->default('EGP');
            $t->decimal('usd_amount', 14, 2)->default(0);
            $t->timestamps();
        });
        Schema::create('invoice_payments', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('invoice_id')->nullable();
            $t->decimal('value', 14, 2)->default(0);
            $t->string('currency', 3)->default('EGP');
            $t->string('payment_type')->nullable();
            $t->timestamp('check_paid_at')->nullable();
            $t->timestamps();
        });
    }

    public function test_hybrid_invoice_splits_egp_and_usd_buckets(): void
    {
        $invoice = Invoice::create(['currency' => 'EGP', 'usd_amount' => 500]);
        InvoicePayment::create([
            'invoice_id' => $invoice->id,
            'value' => 10000,
            'currency' => 'EGP',
            'payment_type' => 'bank_transfer',
        ]);
        InvoicePayment::create([
            'invoice_id' => $invoice->id,
            'value' => 200,
            'currency' => 'USD',
            'payment_type' => 'bank_transfer',
        ]);
        $invoice->load('invoicePayments');

        $totals = CompanyCurrencyLedger::invoiceTotals($invoice, fn () => 50000.0);
        $this->assertSame(50000.0, $totals[InvoiceCurrency::EGP]);
        $this->assertSame(500.0, $totals[InvoiceCurrency::USD]);

        $paid = CompanyCurrencyLedger::invoicePaid($invoice);
        $this->assertSame(10000.0, $paid[InvoiceCurrency::EGP]);
        $this->assertSame(200.0, $paid[InvoiceCurrency::USD]);

        $remaining = CompanyCurrencyLedger::invoiceRemaining($invoice, fn () => 50000.0);
        $this->assertSame(40000.0, $remaining[InvoiceCurrency::EGP]);
        $this->assertSame(300.0, $remaining[InvoiceCurrency::USD]);
    }

    public function test_pure_usd_invoice_lives_only_in_usd_bucket(): void
    {
        $invoice = Invoice::create(['currency' => 'USD', 'usd_amount' => 0]);
        $invoice->setRelation('invoicePayments', collect());

        $totals = CompanyCurrencyLedger::invoiceTotals($invoice, fn () => 1200.0);
        $this->assertSame(0.0, $totals[InvoiceCurrency::EGP]);
        $this->assertSame(1200.0, $totals[InvoiceCurrency::USD]);
    }
}
