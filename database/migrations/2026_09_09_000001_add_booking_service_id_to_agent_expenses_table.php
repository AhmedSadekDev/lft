<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * إضافة booking_service_id إلى agent_expenses لربط المصروف
     * بالخدمة التي أنشأها (يُستخدم لحذف السجل عند إلغاء الخدمة).
     */
    public function up(): void
    {
        Schema::table('agent_expenses', function (Blueprint $table) {
            if (! Schema::hasColumn('agent_expenses', 'booking_service_id')) {
                $table->unsignedBigInteger('booking_service_id')->nullable()->after('booking_id');
                $table->foreign('booking_service_id')
                      ->references('id')
                      ->on('booking_services')
                      ->onDelete('cascade');
            }
        });
    }

    public function down(): void
    {
        Schema::table('agent_expenses', function (Blueprint $table) {
            if (Schema::hasColumn('agent_expenses', 'booking_service_id')) {
                $table->dropForeign(['booking_service_id']);
                $table->dropColumn('booking_service_id');
            }
        });
    }
};
