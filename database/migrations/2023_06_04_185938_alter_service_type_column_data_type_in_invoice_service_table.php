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
        Schema::table('invoice_services', function (Blueprint $table) {
            $table->bigInteger('service_category_id')->unsigned()->nullable();
            $table->foreign('service_category_id')->references('id')->on('service_categories')->onDelete('set null');
            $table->dropColumn('service_type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('invoice_services', function (Blueprint $table) {
            $table->dropForeign(['service_category_id']);
            $table->dropColumn('service_category_id');
            $table->string('service_type');
        });
    }
};
