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
        Schema::table('third_bookings', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropColumn('branch_id');
            $table->dropColumn('arrival_date');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('third_bookings', function (Blueprint $table) {
            $table->bigInteger('branch_id')->unsigned()->nullable()->after('factory_id');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('set null');
            $table->date('arrival_date')->nullable()->after('branch_id');
        });
    }
};
