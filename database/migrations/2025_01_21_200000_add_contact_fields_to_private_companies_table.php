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
        Schema::table('private_companies', function (Blueprint $table) {
            $table->string('phone1')->nullable()->after('logo');
            $table->string('phone2')->nullable()->after('phone1');
            $table->string('tel_fax')->nullable()->after('phone2');
            $table->string('email')->nullable()->after('tel_fax');
            $table->text('address')->nullable()->after('email');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('private_companies', function (Blueprint $table) {
            $table->dropColumn(['phone1', 'phone2', 'tel_fax', 'email', 'address']);
        });
    }
};
