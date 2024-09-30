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
        DB::statement('DROP TABLE IF EXISTS temp_invoice_transportations;');
        DB::statement('CREATE TABLE temp_invoice_transportations SELECT * FROM invoice_transportations;');
        Schema::table('invoice_transportations', function (Blueprint $table) {
            $table->dropColumn('container_number');
            $table->dropColumn('sail_of_number');
        });
        Schema::table('invoice_transportations', function (Blueprint $table) {
            $table->string('container_number')->nullable()
                ->after('id');
            $table->string('sail_of_number')->nullable()
                ->after('container_number');
        });
        DB::statement('TRUNCATE TABLE invoice_transportations;');
        DB::statement('INSERT INTO invoice_transportations SELECT * FROM temp_invoice_transportations;');
        DB::statement('TRUNCATE TABLE temp_invoice_transportations;');
        DB::statement('DROP TABLE temp_invoice_transportations;');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP TABLE IF EXISTS temp_invoice_transportations;');
        DB::statement('CREATE TABLE temp_invoice_transportations SELECT * FROM invoice_transportations;');
        Schema::table('invoice_transportations', function (Blueprint $table) {
            $table->dropColumn('container_number');
            $table->dropColumn('sail_of_number');
        });
        Schema::table('invoice_transportations', function (Blueprint $table) {
            $table->bigInteger('container_number')->unsigned()->nullable()
                ->after('id');
            $table->bigInteger('sail_of_number')->nullable()->after('container_number');
        });
        DB::statement('TRUNCATE TABLE invoice_transportations;');
        DB::statement('INSERT INTO invoice_transportations SELECT * FROM temp_invoice_transportations;');
        DB::statement('TRUNCATE TABLE temp_invoice_transportations;');
        DB::statement('DROP TABLE temp_invoice_transportations;');
    }
};
