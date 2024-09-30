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
        Schema::create('booking_movements', function (Blueprint $table) {
            $table->id();

            $table->string('status')->nullable();

            $table->bigInteger('booking_id')->unsigned()->nullable();
            $table->foreign('booking_id')->references('id')->on('bookings')->onDelete('cascade');

            $table->bigInteger('container_id')->unsigned()->nullable();
            $table->foreign('container_id')->references('id')->on('containers')->onDelete('cascade');

            $table->string('description')->nullable();

            $table->string('place_of_activity')->nullable();

            $table->date('date')->nullable();
            $table->time('time')->nullable();

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
        Schema::dropIfExists('booking_movements');
    }
};
