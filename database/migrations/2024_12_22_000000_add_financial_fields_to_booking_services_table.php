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
            $table->bigInteger('vault_id')->unsigned()->nullable()->after('price');
            $table->foreign('vault_id')->references('id')->on('vaults')->onDelete('set null');

            $table->bigInteger('bank_id')->unsigned()->nullable()->after('vault_id');
            $table->foreign('bank_id')->references('id')->on('banks')->onDelete('set null');

            $table->bigInteger('created_by')->unsigned()->nullable()->after('bank_id');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');

            $table->bigInteger('updated_by')->unsigned()->nullable()->after('created_by');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
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
            $table->dropForeign(['vault_id']);
            $table->dropColumn('vault_id');

            $table->dropForeign(['bank_id']);
            $table->dropColumn('bank_id');

            $table->dropForeign(['created_by']);
            $table->dropColumn('created_by');

            $table->dropForeign(['updated_by']);
            $table->dropColumn('updated_by');
        });
    }
};

