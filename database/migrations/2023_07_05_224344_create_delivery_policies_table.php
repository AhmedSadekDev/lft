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
        Schema::create('delivery_policies', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('car_id')->unsigned()->nullable();
            $table->foreign('car_id')->references('id')->on('cars')->onDelete('set null');
            $table->bigInteger('driver_id')->unsigned()->nullable();
            $table->foreign('driver_id')->references('id')->on('drivers')->onDelete('set null');
            $table->tinyInteger('is_settled')->unsigned()->default(0);

            $table->timestamps();
        });

        Schema::create('delivery_policy_containers', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('delivery_policy_id')->unsigned()->nullable();
            $table->foreign('delivery_policy_id')->references('id')->on('delivery_policies')->onDelete('cascade');
            $table->bigInteger('booking_container_id')->unsigned()->nullable();
            $table->foreign('booking_container_id')->references('id')->on('booking_containers')->onDelete('set null');
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
        Schema::dropIfExists('delivery_policies');
    }
};
