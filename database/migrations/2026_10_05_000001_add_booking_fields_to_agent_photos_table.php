<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agent_photos', function (Blueprint $table) {
            $table->unsignedBigInteger('booking_id')->nullable()->after('original_name')->index();
            $table->unsignedBigInteger('booking_container_id')->nullable()->after('booking_id')->index();
            $table->unsignedBigInteger('booking_paper_id')->nullable()->after('booking_container_id')->index();
            $table->timestamp('assigned_at')->nullable()->after('booking_paper_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('agent_photos', function (Blueprint $table) {
            $table->dropColumn(['booking_id', 'booking_container_id', 'booking_paper_id', 'assigned_at']);
        });
    }
};
