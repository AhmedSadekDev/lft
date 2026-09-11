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
            $table->boolean('is_in_loading')->default(0)->after('superagent_specification_approved');
            $table->timestamp('moved_to_loading_at')->nullable()->after('is_in_loading');
        });

        Schema::table('booking_container_agents', function (Blueprint $table) {
            $table->boolean('is_in_loading')->default(0)->after('superagent_specification_approved');
            $table->timestamp('moved_to_loading_at')->nullable()->after('is_in_loading');
        });

        Schema::table('daily_booking_containers', function (Blueprint $table) {
            $table->boolean('is_in_loading')->default(0)->after('superagent_specification_approved');
            $table->timestamp('moved_to_loading_at')->nullable()->after('is_in_loading');
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
            $table->dropColumn(['is_in_loading', 'moved_to_loading_at']);
        });

        Schema::table('booking_container_agents', function (Blueprint $table) {
            $table->dropColumn(['is_in_loading', 'moved_to_loading_at']);
        });

        Schema::table('daily_booking_containers', function (Blueprint $table) {
            $table->dropColumn(['is_in_loading', 'moved_to_loading_at']);
        });
    }
};
