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
            $table->timestamp('specification_completed_at')->nullable()->after('superagent_specification_approved');
            $table->timestamp('specification_approved_at')->nullable()->after('specification_completed_at');
            $table->timestamp('loading_completed_at')->nullable()->after('superagent_loading_approved');
            $table->timestamp('loading_approved_at')->nullable()->after('loading_completed_at');
            $table->timestamp('unloading_completed_at')->nullable()->after('superagent_unloading_approved');
            $table->timestamp('unloading_approved_at')->nullable()->after('unloading_completed_at');
        });

        Schema::table('booking_container_agents', function (Blueprint $table) {
            $table->timestamp('specification_completed_at')->nullable()->after('superagent_specification_approved');
            $table->timestamp('specification_approved_at')->nullable()->after('specification_completed_at');
            $table->timestamp('loading_completed_at')->nullable()->after('superagent_loading_approved');
            $table->timestamp('loading_approved_at')->nullable()->after('loading_completed_at');
            $table->timestamp('unloading_completed_at')->nullable()->after('superagent_unloading_approved');
            $table->timestamp('unloading_approved_at')->nullable()->after('unloading_completed_at');
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
            $table->dropColumn([
                'specification_completed_at',
                'specification_approved_at',
                'loading_completed_at',
                'loading_approved_at',
                'unloading_completed_at',
                'unloading_approved_at',
            ]);
        });

        Schema::table('booking_container_agents', function (Blueprint $table) {
            $table->dropColumn([
                'specification_completed_at',
                'specification_approved_at',
                'loading_completed_at',
                'loading_approved_at',
                'unloading_completed_at',
                'unloading_approved_at',
            ]);
        });
    }
};
