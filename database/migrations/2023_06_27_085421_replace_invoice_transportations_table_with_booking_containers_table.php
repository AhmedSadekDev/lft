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
        Schema::table('booking_containers', function (Blueprint $table) {
            $table->json('container_json')->nullable();
            $table->bigInteger('departure_id')->unsigned()->nullable();
            $table->foreign('departure_id')->references('id')->on('cities_and_regions')->onDelete('set null');
            $table->json('departure_json')->nullable();
            $table->bigInteger('loading_id')->unsigned()->nullable();
            $table->foreign('loading_id')->references('id')->on('cities_and_regions')->onDelete('set null');
            $table->json('loading_json')->nullable();
            $table->bigInteger('aging_id')->unsigned()->nullable();
            $table->foreign('aging_id')->references('id')->on('cities_and_regions')->onDelete('set null');
            $table->json('aging_json')->nullable();
            $table->integer('price')->unsigned()->nullable();

            $table->foreign('yard_id')->references('id')->on('yards')->onDelete('set null');
            $table->json('yard_json')
                ->nullable()
                ->after('yard_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('booking_containers', function (Blueprint $table) {
            $table->dropColumn('container_json');
            $table->dropForeign(['departure_id'])->ifExists();
            $table->dropColumn('departure_id');
            $table->dropColumn('departure_json');
            $table->dropForeign(['loading_id'])->ifExists();
            $table->dropColumn('loading_id');
            $table->dropColumn('loading_json');
            $table->dropForeign(['aging_id'])->ifExists();
            $table->dropColumn('aging_id');
            $table->dropColumn('aging_json');
            $table->dropColumn('price');
            $table->dropForeign(['yard_id'])->ifExists();
            $table->dropColumn('yard_json');
        });
    }
};
