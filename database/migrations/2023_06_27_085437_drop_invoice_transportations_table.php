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
        Schema::dropIfExists('invoice_transportations');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::create('invoice_transportations', function (Blueprint $table) {
            $table->id();

            $table->string('container_number')->nullable();
            $table->string('sail_of_number')->nullable();


            $table->bigInteger('invoice_id')->unsigned()->nullable();
            $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('set null');

            $table->bigInteger('booking_id')->unsigned()->nullable();
            $table->foreign('booking_id')->references('id')->on('bookings')->onDelete('set null');


            $table->bigInteger('container_id')->unsigned()->nullable();
            $table->foreign('container_id')->references('id')->on('containers')->onDelete('set null');
            $table->text('container_data')->nullable();

            $table->bigInteger('factory_id')->unsigned()->nullable();
            $table->foreign('factory_id')->references('id')->on('factories')->onDelete('set null');
            $table->text('factory_data')->nullable();

            $table->bigInteger('branch_id')->unsigned()->nullable();
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('set null');
            $table->text('branch_data')->nullable();

            $table->bigInteger('departure_id')->unsigned()->nullable();
            $table->foreign('departure_id')->references('id')->on('cities_and_regions')->onDelete('set null');

            $table->bigInteger('loading_id')->unsigned()->nullable();
            $table->foreign('loading_id')->references('id')->on('cities_and_regions')->onDelete('set null');

            $table->bigInteger('aging_id')->unsigned()->nullable();
            $table->foreign('aging_id')->references('id')->on('cities_and_regions')->onDelete('set null');

            $table->integer('price')->unsigned()->nullable();

            $table->timestamps();
        });
    }
};
