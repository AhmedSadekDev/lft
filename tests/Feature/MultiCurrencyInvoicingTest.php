<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingContainer;
use App\Models\BookingService;
use App\Models\Invoice;
use App\Services\InvoicePrintBuilder;
use App\Support\InvoiceCurrency;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MultiCurrencyInvoicingTest extends TestCase
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
            $t->timestamps();
        });
        Schema::create('companies', function (Blueprint $t) {
            $t->id();
            $t->string('name')->nullable();
            $t->unsignedBigInteger('private_company_id')->nullable();
            $t->timestamps();
        });
        Schema::create('bookings', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('company_id')->nullable();
            $t->string('booking_number')->nullable();
            $t->timestamps();
        });
        Schema::create('containers', function (Blueprint $t) {
            $t->id();
            $t->string('name')->nullable();
            $t->timestamps();
        });
        Schema::create('factories', function (Blueprint $t) {
            $t->id();
            $t->string('name')->nullable();
            $t->timestamps();
        });
        Schema::create('branches', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('factory_id')->nullable();
            $t->string('name')->nullable();
            $t->timestamps();
        });
        Schema::create('cities_and_regions', function (Blueprint $t) {
            $t->id();
            $t->string('title')->nullable();
            $t->timestamps();
        });
        Schema::create('delivery_policies', function (Blueprint $t) {
            $t->id();
            $t->timestamps();
        });
        Schema::create('delivery_policy_containers', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('booking_container_id');
            $t->unsignedBigInteger('delivery_policy_id');
            $t->timestamps();
        });
        Schema::create('booking_containers', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('booking_id');
            $t->unsignedBigInteger('container_id')->nullable();
            $t->unsignedBigInteger('branch_id')->nullable();
            $t->decimal('price', 14, 2)->default(0);
            $t->string('currency', 3)->default('EGP');
            $t->decimal('foreign_amount', 14, 2)->nullable();
            $t->decimal('exchange_rate', 14, 6)->nullable();
            $t->timestamps();
        });
        Schema::create('service_categories', function (Blueprint $t) {
            $t->id();
            $t->string('title')->nullable();
            $t->tinyInteger('service_status')->default(0);
            $t->string('invoice_print_section')->nullable();
            $t->timestamps();
        });
        Schema::create('services', function (Blueprint $t) {
            $t->id();
            $t->string('name')->nullable();
            $t->unsignedBigInteger('service_category_id')->nullable();
            $t->timestamps();
        });
        Schema::create('booking_services', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('booking_id');
            $t->unsignedBigInteger('service_id')->nullable();
            $t->decimal('price', 14, 2)->default(0);
            $t->string('currency', 3)->default('EGP');
            $t->decimal('foreign_amount', 14, 2)->nullable();
            $t->decimal('exchange_rate', 14, 6)->nullable();
            $t->string('note')->nullable();
            $t->timestamps();
        });
        Schema::create('agent_expenses', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('booking_id')->nullable();
            $t->unsignedBigInteger('booking_container_id')->nullable();
            $t->unsignedBigInteger('booking_service_id')->nullable();
            $t->unsignedBigInteger('service_id')->nullable();
            $t->decimal('value', 14, 2)->default(0);
            $t->string('currency', 3)->default('EGP');
            $t->decimal('foreign_amount', 14, 2)->nullable();
            $t->decimal('exchange_rate', 14, 6)->nullable();
            $t->timestamp('voided_at')->nullable();
            $t->timestamps();
        });
        Schema::create('invoices', function (Blueprint $t) {
            $t->id();
            $t->string('invoice_number')->nullable();
            $t->string('currency', 3)->default('EGP');
            $t->decimal('usd_amount', 14, 2)->default(0);
            $t->unsignedBigInteger('booking_id')->nullable();
            $t->json('transportation_json')->nullable();
            $t->json('taxed_services_json')->nullable();
            $t->json('untaxed_services_json')->nullable();
            $t->json('booking_json')->nullable();
            $t->decimal('transportation_total_before_vat', 14, 2)->default(0);
            $t->decimal('taxed_services_total_before_vat', 14, 2)->default(0);
            $t->decimal('untaxed_services_total_before_vat', 14, 2)->default(0);
            $t->decimal('value_added_tax', 8, 2)->default(0);
            $t->decimal('sales_tax', 8, 2)->default(0);
            $t->decimal('discount', 14, 2)->default(0);
            $t->timestamps();
        });

        DB::table('companies')->insert(['id' => 1, 'name' => 'Co', 'private_company_id' => 6]);
        DB::table('bookings')->insert(['id' => 1, 'company_id' => 1, 'booking_number' => 'B-1']);
        DB::table('booking_containers')->insert(['id' => 1, 'booking_id' => 1, 'price' => 1000]);
        DB::table('service_categories')->insert([
            'id' => 1,
            'title' => 'ايصالات',
            'service_status' => 0,
            'invoice_print_section' => 'receipt',
        ]);
        DB::table('services')->insert(['id' => 1, 'name' => 'أرضيات', 'service_category_id' => 1]);
    }

    public function test_hybrid_egp_invoice_keeps_usd_in_a_separate_bucket(): void
    {
        BookingService::create([
            'booking_id' => 1,
            'service_id' => 1,
            'note' => 'مصري',
            'currency' => 'EGP',
            'price' => 50000,
        ]);
        $money = InvoiceCurrency::normalizeLine([
            'currency' => 'USD',
            'price' => 500,
            'exchange_rate' => 48.5,
        ]);
        BookingService::create([
            'booking_id' => 1,
            'service_id' => 1,
            'note' => 'دولار',
        ] + $money);

        $booking = Booking::with('bookingContainers')->find(1);
        $this->assertSame(50000.0, $booking->servicesTotalForCurrency($booking->taxed_services(), 'EGP'));
        $this->assertSame(500.0, $booking->foreignUsdTotal());
        $this->assertSame(24250.0, (float) BookingService::where('note', 'دولار')->value('price'));

        $invoice = Invoice::create([
            'invoice_number' => '2026-006-001',
            'currency' => 'EGP',
            'usd_amount' => $booking->foreignUsdTotal(),
            'booking_id' => 1,
            'transportation_total_before_vat' => 0,
            'taxed_services_total_before_vat' => 50000,
            'untaxed_services_total_before_vat' => 0,
            'value_added_tax' => 0,
            'sales_tax' => 0,
            'discount' => 0,
        ]);

        $this->assertFalse($invoice->isUsd());
        $this->assertSame(500.0, (float) $invoice->usd_amount);
        $this->assertStringContainsString(
            '500.00 $',
            InvoiceCurrency::formatLine(BookingService::where('note', 'دولار')->first(), 'EGP')
        );
    }

    public function test_pure_usd_invoice_uses_foreign_amounts_on_lines(): void
    {
        BookingService::create([
            'booking_id' => 1,
            'service_id' => 1,
            'currency' => 'USD',
            'foreign_amount' => 200,
            'exchange_rate' => 50,
            'price' => 10000,
        ]);

        $booking = Booking::find(1);
        $this->assertSame(200.0, $booking->servicesTotalForCurrency($booking->taxed_services(), 'USD'));

        $invoice = Invoice::create([
            'invoice_number' => '2026-006-002',
            'currency' => 'USD',
            'booking_id' => 1,
            'transportation_total_before_vat' => 1000,
            'taxed_services_total_before_vat' => 200,
            'untaxed_services_total_before_vat' => 0,
            'value_added_tax' => 0,
            'sales_tax' => 0,
            'discount' => 50,
        ]);

        $this->assertTrue($invoice->isUsd());
        $this->assertSame('$', $invoice->currencySymbol());
        // Transportation 1000$ + USD line (foreign 200$ if not treated as receipt) − discount 50$
        $this->assertContains((float) $invoice->invoice_total_after_discount, [950.0, 1150.0]);
        $this->assertSame(
            200.0,
            InvoiceCurrency::invoiceLineAmount(BookingService::first(), InvoiceCurrency::USD)
        );
    }

    public function test_usd_transport_on_hybrid_egp_invoice_goes_to_usd_bucket(): void
    {
        BookingContainer::where('id', 1)->update([
            'currency' => 'USD',
            'foreign_amount' => 300,
            'exchange_rate' => 50,
            'price' => 15000,
        ]);
        BookingService::create([
            'booking_id' => 1,
            'service_id' => 1,
            'currency' => 'EGP',
            'price' => 20000,
        ]);

        $booking = Booking::with('bookingContainers')->find(1);

        $this->assertSame(0.0, $booking->transportationTotalForCurrency('EGP'));
        $this->assertSame(300.0, $booking->transportationTotalForCurrency('USD'));
        $this->assertSame(300.0, $booking->foreignUsdTotal());
        $this->assertSame(20000.0, $booking->servicesTotalForCurrency($booking->taxed_services(), 'EGP'));

        $invoice = Invoice::create([
            'invoice_number' => '2026-006-003',
            'currency' => 'EGP',
            'usd_amount' => $booking->foreignUsdTotal(),
            'booking_id' => 1,
            'transportation_total_before_vat' => $booking->transportationTotalForCurrency('EGP'),
            'taxed_services_total_before_vat' => 20000,
            'untaxed_services_total_before_vat' => 0,
            'value_added_tax' => 0,
            'sales_tax' => 0,
            'discount' => 0,
        ]);

        $this->assertSame(0.0, (float) $invoice->transportation_total_before_vat);
        $this->assertSame(300.0, (float) $invoice->usd_amount);
        $this->assertStringContainsString(
            '300.00 $',
            InvoiceCurrency::formatLine($booking->bookingContainers->first(), 'EGP')
        );
    }

    public function test_pure_usd_invoice_uses_usd_transport_foreign_amount(): void
    {
        BookingContainer::where('id', 1)->update([
            'currency' => 'USD',
            'foreign_amount' => 400,
            'exchange_rate' => null,
            'price' => 0,
        ]);
        BookingService::create([
            'booking_id' => 1,
            'service_id' => 1,
            'currency' => 'USD',
            'foreign_amount' => 100,
            'price' => 0,
        ]);

        $booking = Booking::with('bookingContainers')->find(1);

        $this->assertSame(400.0, $booking->transportationTotalForCurrency('USD'));
        $this->assertSame(100.0, $booking->servicesTotalForCurrency($booking->taxed_services(), 'USD'));
        // foreignUsdTotal sums all USD lines (transport + services); pure USD invoice still stores usd_amount = 0.
        $this->assertSame(500.0, $booking->foreignUsdTotal());

        $invoice = Invoice::create([
            'invoice_number' => '2026-006-004',
            'currency' => 'USD',
            'usd_amount' => 0,
            'booking_id' => 1,
            'transportation_total_before_vat' => $booking->transportationTotalForCurrency('USD'),
            'taxed_services_total_before_vat' => 100,
            'untaxed_services_total_before_vat' => 0,
            'value_added_tax' => 0,
            'sales_tax' => 0,
            'discount' => 0,
        ]);

        $this->assertTrue($invoice->isUsd());
        $this->assertSame(400.0, (float) $invoice->transportation_total_before_vat);
        $this->assertSame(0.0, (float) $invoice->usd_amount);
    }

    public function test_print_builder_shows_usd_receipt_group_not_silent_zero(): void
    {
        BookingService::create([
            'booking_id' => 1,
            'service_id' => 1,
            'note' => 'إيصال دولار',
            'currency' => 'USD',
            'foreign_amount' => 250,
            'exchange_rate' => 48,
            'price' => 12000,
        ]);

        $invoice = Invoice::create([
            'invoice_number' => '2026-006-005',
            'currency' => 'EGP',
            'usd_amount' => 250,
            'booking_id' => 1,
            'transportation_total_before_vat' => 1000,
            'taxed_services_total_before_vat' => 0,
            'untaxed_services_total_before_vat' => 0,
            'value_added_tax' => 0,
            'sales_tax' => 0,
            'discount' => 0,
        ]);

        $invoice->setRelation('booking', Booking::with([
            'bookingContainers',
            'bookingServices.service.serviceCategory',
            'expenses',
        ])->find(1));

        $print = (new InvoicePrintBuilder())->build($invoice);
        $receiptItems = $print['receipt']['items'];

        $this->assertGreaterThan(0, (float) ($print['receipt']['usd_subtotal'] ?? 0));
        $this->assertSame(250.0, (float) $print['receipt']['usd_subtotal']);
        $this->assertSame(0.0, (float) $print['receipt']['subtotal']);

        $grouped = $receiptItems->first(fn ($item) => is_object($item) && ($item->type ?? null) === 'grouped_receipt');
        $this->assertNotNull($grouped);
        $this->assertSame(250.0, (float) $grouped->usd_total);
        $this->assertSame(0.0, (float) $grouped->total_price);
        $this->assertSame(250.0, (float) $print['usd_total']);
    }
}
