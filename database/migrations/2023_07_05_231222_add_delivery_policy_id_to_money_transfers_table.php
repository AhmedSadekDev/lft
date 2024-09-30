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
        Schema::table('money_transfers', function (Blueprint $table) {
            if (!Schema::hasColumn('money_transfers', 'delivery_policy_id')) {

                $table->bigInteger('delivery_policy_id')->unsigned()->nullable();
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
        Schema::table('money_transfers', function (Blueprint $table) {
        });
    }
};
