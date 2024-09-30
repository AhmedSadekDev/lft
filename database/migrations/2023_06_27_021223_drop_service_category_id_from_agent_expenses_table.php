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
        Schema::table('agent_expenses', function (Blueprint $table) {
            if (Schema::hasColumn('agent_expenses', 'booking_container_id')) {

                $table->dropForeign(['booking_container_id']);
                $table->dropColumn('booking_container_id');
            }
            if (Schema::hasColumn('agent_expenses', 'service_category_id')) {

                $table->dropForeign(['service_category_id']);
                $table->dropColumn('service_category_id');
            }
            if (!Schema::hasColumn('agent_expenses', 'booking_id')) {

                $table->bigInteger('booking_id')->unsigned()->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    }
};
