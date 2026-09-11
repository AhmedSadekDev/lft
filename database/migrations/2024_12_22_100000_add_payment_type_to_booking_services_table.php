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
        Schema::table('booking_services', function (Blueprint $table) {
            $table->string('payment_type')->nullable()->after('bank_id')->comment('vault, bank, agent');
            $table->bigInteger('agent_id')->unsigned()->nullable()->after('payment_type');
            $table->foreign('agent_id')->references('id')->on('agents')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('booking_services', function (Blueprint $table) {
            $table->dropForeign(['agent_id']);
            $table->dropColumn(['payment_type', 'agent_id']);
        });
    }
};

