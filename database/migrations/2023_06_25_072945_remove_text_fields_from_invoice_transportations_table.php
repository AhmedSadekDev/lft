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
            $table->dropColumn('departure');
            $table->dropColumn('loading');
            $table->dropColumn('aging');
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
            $table->string('departure', 100)->nullable();
            $table->string('loading', 100)->nullable();
            $table->string('aging', 100)->nullable();
        });
    }
};
