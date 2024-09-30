<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
            // remove fields
            $table->dropForeign(['invoice_id'])->ifExists();
            $table->dropColumn('invoice_id');
            $table->dropColumn('service');
            $table->dropForeign(['service_category_id'])->ifExists();
            $table->dropColumn('service_category_id');

            // add fields
            $table->json('service_json')->nullable();
        });
        Schema::rename('invoice_services', 'booking_services');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::rename('booking_services', 'invoice_services');
        Schema::table('invoice_services', function (Blueprint $table) {
            // revert creation
            $table->dropColumn('service_json');

            // revert deletion
            $table->bigInteger('invoice_id')->unsigned()->nullable();
            $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('set null');
            $table->string('service', 100)->nullable();
            $table->bigInteger('service_category_id')->unsigned()->nullable();
            $table->foreign('service_category_id')->references('id')->on('service_categories')->onDelete('set null');
        });
    }
};
