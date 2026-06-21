<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payingcars', function (Blueprint $table) {
            if (!Schema::hasColumn('payingcars', 'payment_group_uuid')) {
                $table->uuid('payment_group_uuid')->nullable()->after('user_id');
                $table->index('payment_group_uuid');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payingcars', function (Blueprint $table) {
            if (Schema::hasColumn('payingcars', 'payment_group_uuid')) {
                $table->dropColumn('payment_group_uuid');
            }
        });
    }
};
