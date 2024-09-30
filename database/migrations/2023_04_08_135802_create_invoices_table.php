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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();

            $table->tinyInteger('taxed')->nullable()->comment('0 => Not Taxed, 1 => Taxed');

            $table->bigInteger('booking_id')->unsigned()->nullable();
            $table->foreign('booking_id')->references('id')->on('bookings')->onDelete('set null');

            $table->bigInteger('invoice_number')->unsigned()->nullable();

            $table->double('transportation_before_vat', 15, 2)->nullable();
            $table->tinyInteger('is_vated')->nullable()->comment('0 => Not Taxed, 1 => Taxed');
            $table->integer('vat')->nullable();
            $table->double('transportation_after_vat', 15, 2)->nullable();

            $table->double('transportation_extensions_fees', 15, 2)->nullable();

            $table->double('total_before_discount', 15, 2)->nullable();
            $table->double('total_after_discount', 15, 2)->nullable();
            $table->double('total_additional_discount', 15, 2)->nullable();

            $table->text('transportation_data')->nullable();
            $table->text('extensions_data')->nullable();
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
    }
};
