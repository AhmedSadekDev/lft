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
            $table->bigInteger('container_id')->unsigned()->nullable()->after('factory_id');
            $table->foreign('container_id')->references('id')->on('containers')->onDelete('set null');
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
            $table->dropForeign(['container_id']);
            $table->dropColumn('container_id');
        });
    }
};
