<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('invoice_number_auto_increment')->nullable();
        });
        DB::statement('UPDATE companies SET companies.invoice_number_auto_increment = (SELECT MAX(invoices.invoice_number) as max_invoice_number FROM invoices WHERE (SELECT company_id FROM bookings WHERE invoices.booking_id = bookings.id) = companies.id)');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('invoice_number_auto_increment');
        });
    }
};
