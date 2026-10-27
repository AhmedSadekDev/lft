<?php

namespace Tests\Unit;

use App\Models\Booking;
use App\Models\BookingContainer;
use App\Models\Company;
use App\Models\CompanyTransportation;
use App\Models\Container;
use App\Services\CompanyTransportationPriceResolver;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CompanyTransportationPriceResolverTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        DB::reconnect('sqlite');

        Schema::create('companies', function (Blueprint $t) {
            $t->id();
            $t->string('name')->nullable();
            $t->timestamps();
        });
        Schema::create('containers', function (Blueprint $t) {
            $t->id();
            $t->string('type')->nullable();
            $t->timestamps();
        });
        Schema::create('bookings', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('company_id')->nullable();
            $t->string('public_code', 5)->nullable();
            $t->timestamps();
        });
        Schema::create('invoices', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('booking_id');
        });
        Schema::create('booking_containers', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('booking_id');
            $t->unsignedBigInteger('container_id')->nullable();
            $t->unsignedBigInteger('departure_id')->nullable();
            $t->unsignedBigInteger('loading_id')->nullable();
            $t->unsignedBigInteger('aging_id')->nullable();
            $t->decimal('price', 14, 2)->default(0);
            $t->timestamps();
        });
        Schema::create('company_transportations', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('company_id');
            $t->unsignedBigInteger('container_id');
            $t->unsignedBigInteger('departure_id');
            $t->unsignedBigInteger('loading_id');
            $t->unsignedBigInteger('aging_id');
            $t->decimal('price', 14, 2)->default(0);
            $t->timestamps();
        });

        $this->nasijoonId = Company::create(['name' => 'النساجون'])->id;
        $this->checkpointId = Company::create(['name' => 'تشيك بوينت'])->id;
        $this->containerTypeId = Container::create(['type' => '40'])->id;

        // Same Damietta→10th route keys; different company prices.
        CompanyTransportation::create([
            'company_id' => $this->nasijoonId,
            'container_id' => $this->containerTypeId,
            'departure_id' => 10,
            'loading_id' => 20,
            'aging_id' => 30,
            'price' => 14500,
        ]);
        CompanyTransportation::create([
            'company_id' => $this->checkpointId,
            'container_id' => $this->containerTypeId,
            'departure_id' => 10,
            'loading_id' => 20,
            'aging_id' => 30,
            'price' => 18000,
        ]);
    }

    private int $nasijoonId;
    private int $checkpointId;
    private int $containerTypeId;

    public function test_does_not_apply_other_company_tariff(): void
    {
        $booking = Booking::create(['company_id' => $this->checkpointId]);
        $container = BookingContainer::create([
            'booking_id' => $booking->id,
            'container_id' => $this->containerTypeId,
            'departure_id' => 10,
            'loading_id' => 20,
            'aging_id' => 30,
            'price' => 0,
        ]);

        $applied = app(CompanyTransportationPriceResolver::class)->applyToContainer($container);

        $this->assertSame(18000.0, $applied);
        $this->assertSame(18000.0, (float) $container->fresh()->price);
    }

    public function test_nasijoon_keeps_own_price(): void
    {
        $booking = Booking::create(['company_id' => $this->nasijoonId]);
        $container = BookingContainer::create([
            'booking_id' => $booking->id,
            'container_id' => $this->containerTypeId,
            'departure_id' => 10,
            'loading_id' => 20,
            'aging_id' => 30,
            'price' => 0,
        ]);

        $applied = app(CompanyTransportationPriceResolver::class)->applyToContainer($container);

        $this->assertSame(14500.0, $applied);
        $this->assertSame(14500.0, (float) $container->fresh()->price);
    }

    public function test_missing_company_tariff_leaves_price_unchanged(): void
    {
        $otherId = Company::create(['name' => 'Other'])->id;
        $booking = Booking::create(['company_id' => $otherId]);
        $container = BookingContainer::create([
            'booking_id' => $booking->id,
            'container_id' => $this->containerTypeId,
            'departure_id' => 10,
            'loading_id' => 20,
            'aging_id' => 30,
            'price' => 0,
        ]);

        $applied = app(CompanyTransportationPriceResolver::class)->applyToContainer($container);

        $this->assertNull($applied);
        $this->assertSame(0.0, (float) $container->fresh()->price);
    }

    public function test_apply_to_booking_fills_zero_prices_before_invoice(): void
    {
        $booking = Booking::create(['company_id' => $this->checkpointId]);
        $zero = BookingContainer::create([
            'booking_id' => $booking->id,
            'container_id' => $this->containerTypeId,
            'departure_id' => 10,
            'loading_id' => 20,
            'aging_id' => 30,
            'price' => 0,
        ]);
        BookingContainer::create([
            'booking_id' => $booking->id,
            'container_id' => $this->containerTypeId,
            'departure_id' => 99,
            'loading_id' => 20,
            'aging_id' => 30,
            'price' => 0,
        ]);

        $count = app(CompanyTransportationPriceResolver::class)->applyToBooking($booking->fresh());

        $this->assertSame(1, $count);
        $this->assertSame(18000.0, (float) $zero->fresh()->price);
    }
}
