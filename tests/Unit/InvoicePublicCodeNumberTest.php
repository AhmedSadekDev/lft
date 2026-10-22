<?php

namespace Tests\Unit;

use App\Models\Booking;
use App\Models\Invoice;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class InvoicePublicCodeNumberTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('bookings', function (Blueprint $t) {
            $t->id();
            $t->string('booking_number')->nullable();
            $t->string('public_code', 5)->nullable()->unique();
            $t->unsignedBigInteger('company_id')->nullable();
            $t->timestamps();
        });
        Schema::create('invoices', function (Blueprint $t) {
            $t->id();
            $t->string('invoice_number')->nullable();
            $t->unsignedBigInteger('booking_id')->nullable();
            $t->timestamps();
        });
    }

    public function test_invoice_number_uses_public_code_and_yearly_serial(): void
    {
        $year = date('Y');
        DB::table('invoices')->insert(['invoice_number' => $year.'-006-012', 'booking_id' => 99]);

        $booking = Booking::create([
            'booking_number' => 'SHIP-1',
            'public_code' => '48291',
            'company_id' => 6,
        ]);

        $number = Invoice::getNextInvoiceNumberForBooking($booking);
        $this->assertSame($year.'-48291-013', $number);

        $allocated = Invoice::allocateUniqueInvoiceNumber($booking);
        $this->assertSame($year.'-48291-013', $allocated);
        DB::table('invoices')->insert(['invoice_number' => $allocated, 'booking_id' => $booking->id]);

        $booking2 = Booking::create([
            'booking_number' => 'SHIP-2',
            'public_code' => '71933',
            'company_id' => 7,
        ]);
        $this->assertSame($year.'-71933-014', Invoice::getNextInvoiceNumberForBooking($booking2));
    }

    public function test_booking_auto_assigns_five_digit_public_code(): void
    {
        $booking = Booking::create(['booking_number' => 'AUTO-1']);
        $this->assertMatchesRegularExpression('/^\d{5}$/', (string) $booking->public_code);
    }
}
