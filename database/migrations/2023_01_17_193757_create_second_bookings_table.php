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
        Schema::create('second_bookings', function (Blueprint $table) {
            $table->id();

            $table->bigInteger('booking_id')->unsigned()->nullable();
            $table->foreign('booking_id')->references('id')->on('bookings')->onDelete('cascade');

            $table->string('shipping_agency', 100)->nullable();
            $table->integer('booking_number')->unsigned()->nullable();
            $table->bigInteger('certificate_number')->unsigned()->nullable();
            $table->string('type_of_service', 100)->nullable();
            $table->string('type_of_action', 100)->nullable();
            $table->date('discharge_date')->nullable();
            $table->date('permit_end_date')->nullable();

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
        Schema::dropIfExists('second_bookings');
    }
};
