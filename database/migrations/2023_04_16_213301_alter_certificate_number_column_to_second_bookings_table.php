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
            $table->string('certificate_number', 100)->nullable()->change();
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
            //$table->bigInteger('certificate_number')->unsigned()->nullable()->change();
        });
    }
};
