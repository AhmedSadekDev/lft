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
        Schema::table('service_categories', function (Blueprint $table) {
            $table->string('service_status', 100)->nullable()->comment('0 => taxed, 1 => untaxed, 2 => not_added_to_invoice');
        });

        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn('service_status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('service_categories', function (Blueprint $table) {
            $table->dropColumn('service_status');
        });

        Schema::table('services', function (Blueprint $table) {
            $table->string('service_status', 100)->nullable()->comment('0 => taxed, 1 => untaxed, 2 => not_added_to_invoice');
        });
    }
};
