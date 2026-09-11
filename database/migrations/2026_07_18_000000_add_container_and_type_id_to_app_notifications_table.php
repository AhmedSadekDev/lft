<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('app_notifications', function (Blueprint $table) {
            $table->foreignId('booking_container_id')
                ->nullable()
                ->after('type')
                ->constrained('booking_containers')
                ->nullOnDelete();
            $table->unsignedTinyInteger('type_id')
                ->nullable()
                ->after('booking_container_id')
                ->comment('0: specification, 1: loading, 2: unloading');
        });
    }

    public function down(): void
    {
        Schema::table('app_notifications', function (Blueprint $table) {
            $table->dropForeign(['booking_container_id']);
            $table->dropColumn(['booking_container_id', 'type_id']);
        });
    }
};
