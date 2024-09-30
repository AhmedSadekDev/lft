<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
        Schema::dropIfExists('invoices');
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();

            $table->string('invoice_number')
                ->unique();

            $table->bigInteger('booking_id')->unsigned()->nullable();
            $table->foreign('booking_id')->references('id')->on('bookings')
                ->onDelete('set null');

            $table->json('transportation_json')->nullable();
            $table->json('taxed_services_json')->nullable();
            $table->json('untaxed_services_json')->nullable();

            $table->double('transportation_total_before_vat', 8, 2)->default(0);
            $table->double('taxed_services_total_before_vat', 8, 2, true)->default(0);
            $table->double('untaxed_services_total_before_vat', 8, 2, true)->default(0);

            $table->double('value_added_tax', 8, 2, true)->default(0);
            $table->double('sales_tax', 8, 2, true)->default(0);
            $table->double('discount', 8, 2, true)->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('invoices');
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('booking_id')->unsigned()->nullable();
            $table->foreign('booking_id')->references('id')->on('bookings')->onDelete('set null');
            $table->bigInteger('company_id')->unsigned()->nullable();
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('set null');
            $table->bigInteger('invoice_number')->unsigned()->nullable();
            $table->text('transportation_data')->nullable();
            $table->double('transportation_before_vat', 15, 2)->nullable();
            $table->text('extensions_data')->nullable();
            $table->double('transportation_extensions_fees', 15, 2)->nullable();
            $table->double('total_before_discount', 15, 2)->nullable();
            $table->integer('discount')->nullable();
            $table->double('total_after_discount', 15, 2)->nullable();
            $table->integer('vat')->nullable();
            $table->tinyInteger('is_vated')->nullable()->comment('0 => Not Taxed, 1 => Taxed');
            $table->double('transportation_after_vat', 15, 2)->nullable();
            $table->double('total_additional_discount', 15, 2)->nullable();
            $table->tinyInteger('taxed')->nullable()->comment('0 => Not Taxed, 1 => Taxed');
            $table->timestamps();
        });
    }
};
