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
        Schema::create('booking_container_agents', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('booking_container_id')->unsigned()->nullable();
            $table->foreign('booking_container_id')->references('id')->on('booking_containers')->onDelete('set null');
            $table->bigInteger('agent_id')->unsigned()->nullable();
            $table->foreign('agent_id')->references('id')->on('agents')->onDelete('set null');

            $table->tinyInteger('booking_container_status')->nullable()->comment('0 => specification,1 => loading, 2 => unloading ,3 => finished');
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
        Schema::dropIfExists('booking_container_agents');
    }
};
