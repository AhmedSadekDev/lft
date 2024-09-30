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
            $table->bigInteger('branch_id')->unsigned()->nullable()->after('container_id');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('set null');
            $table->date('arrival_date')->nullable()->after('container_no');
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
            $table->dropForeign(['branch_id']);
            $table->dropColumn('branch_id');
            $table->dropColumn('arrival_date');

        });
    }
};
