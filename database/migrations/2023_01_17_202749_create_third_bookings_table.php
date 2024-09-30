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
        Schema::create('third_bookings', function (Blueprint $table) {
            $table->id();

            $table->bigInteger('booking_id')->unsigned()->nullable();
            $table->foreign('booking_id')->references('id')->on('bookings')->onDelete('cascade');

            $table->float('quantity')->nullable();
            $table->string('unit', 100)->nullable();

            $table->bigInteger('factory_id')->unsigned()->nullable();
            $table->foreign('factory_id')->references('id')->on('factories')->onDelete('cascade');

            $table->bigInteger('branch_id')->unsigned()->nullable();
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');

            $table->date('arrival_date')->nullable();

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
        Schema::dropIfExists('third_bookings');
    }
};
