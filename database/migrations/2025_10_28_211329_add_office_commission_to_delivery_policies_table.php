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
        Schema::table('delivery_policies', function (Blueprint $table) {
            $table->decimal('office_commission', 10, 2)->default(0)->after('is_settled')->comment('دخان المكتب');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('delivery_policies', function (Blueprint $table) {
            $table->dropColumn('office_commission');
        });
    }
};
