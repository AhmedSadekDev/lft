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
        Schema::create('company_transportations', function (Blueprint $table) {
            $table->id();


            $table->bigInteger('company_id')->unsigned()->nullable();
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('set null');
            $table->text('company_data')->nullable();

            $table->bigInteger('container_id')->unsigned()->nullable();
            $table->foreign('container_id')->references('id')->on('containers')->onDelete('set null');
            $table->text('container_data')->nullable();

            $table->bigInteger('departure_id')->unsigned()->nullable();
            $table->foreign('departure_id')->references('id')->on('cities_and_regions')->onDelete('set null');
            $table->string('departure', 100)->nullable();

            $table->bigInteger('loading_id')->unsigned()->nullable();
            $table->foreign('loading_id')->references('id')->on('cities_and_regions')->onDelete('set null');
            $table->string('loading', 100)->nullable();

            $table->bigInteger('aging_id')->unsigned()->nullable();
            $table->foreign('aging_id')->references('id')->on('cities_and_regions')->onDelete('set null');
            $table->string('aging', 100)->nullable();

            $table->integer('price')->unsigned()->nullable();

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
        Schema::dropIfExists('company_transportations');
    }
};
