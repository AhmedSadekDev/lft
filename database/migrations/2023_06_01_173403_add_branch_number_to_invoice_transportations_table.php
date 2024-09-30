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
        Schema::table('invoice_transportations', function (Blueprint $table) {
            $table->bigInteger('branch_id')->unsigned()->nullable()->after('factory_data');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('set null');
            $table->text('branch_data')->nullable()->after('branch_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('invoice_transportations', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropColumn('branch_id');
            $table->dropColumn('branch_data');
        });
    }
};
