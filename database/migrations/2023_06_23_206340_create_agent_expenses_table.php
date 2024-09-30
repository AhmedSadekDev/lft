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
        Schema::create('agent_expenses', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('agent_id')->unsigned()->nullable();
            $table->foreign('agent_id')->references('id')->on('agents')->onDelete('cascade');
            $table->tinyInteger('type')->default(1)->comment("1 => general; 2 => reservation");
            $table->text('notes')->nullable();
            $table->bigInteger('booking_container_id')->unsigned()->nullable();
            $table->foreign('booking_container_id')->references('id')->on('booking_containers')->onDelete('set null');
            $table->bigInteger('service_category_id')->unsigned()->nullable();
            $table->foreign('service_category_id')->references('id')->on('service_categories')->onDelete('set null');
            $table->bigInteger('service_id')->unsigned()->nullable();
            $table->foreign('service_id')->references('id')->on('services')->onDelete('set null');
            $table->string('image')->nullable();
            $table->double('value')->nullable();

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
        Schema::dropIfExists('agent_expenses');
    }
};
