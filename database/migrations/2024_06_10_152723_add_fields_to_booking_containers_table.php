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
            $table->boolean('superagent_specification_approved')->default(0);
            $table->boolean('superagent_loading_approved')->default(0);
            $table->boolean('superagent_unloading_approved')->default(0);
        });

        Schema::table('booking_container_agents', function (Blueprint $table) {
            $table->boolean('superagent_specification_approved')->default(0);
            $table->boolean('superagent_loading_approved')->default(0);
            $table->boolean('superagent_unloading_approved')->default(0);
        });

        Schema::table('daily_booking_containers', function (Blueprint $table) {
            $table->boolean('superagent_specification_approved')->default(0);
            $table->boolean('superagent_loading_approved')->default(0);
            $table->boolean('superagent_unloading_approved')->default(0);
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
            $table->dropColumn('superagent_specification_approved');
            $table->dropColumn('superagent_loading_approved');
            $table->dropColumn('superagent_unloading_approved');
        });

        Schema::table('booking_container_agents', function (Blueprint $table) {
            $table->dropColumn('superagent_specification_approved');
            $table->dropColumn('superagent_loading_approved');
            $table->dropColumn('superagent_unloading_approved');
        });

        Schema::table('daily_booking_containers', function (Blueprint $table) {
            $table->dropColumn('superagent_specification_approved');
            $table->dropColumn('superagent_loading_approved');
            $table->dropColumn('superagent_unloading_approved');
        });
    }
};
