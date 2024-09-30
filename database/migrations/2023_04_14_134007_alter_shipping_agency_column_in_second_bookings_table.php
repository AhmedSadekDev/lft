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
        Schema::table('second_bookings', function (Blueprint $table) {
            $table->renameColumn('shipping_agency', 'shipping_agent');
            $table->bigInteger('shipping_agent_id')->unsigned()->nullable()->after('booking_id');
            $table->foreign('shipping_agent_id')->references('id')->on('shipping_agents')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('second_bookings', function (Blueprint $table) {
            $table->renameColumn('shipping_agent', 'shipping_agency');
            $table->dropForeign(['shipping_agent_id']);
            $table->dropColumn('shipping_agent_id');
        });
    }
};
