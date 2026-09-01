<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('booking_services')) {
            Schema::table('booking_services', function (Blueprint $table) {
                if (!Schema::hasColumn('booking_services', 'supplier_id')) {
                    $table->foreignId('supplier_id')
                        ->nullable()
                        ->after('agent_id')
                        ->constrained('suppliers')
                        ->nullOnDelete();
                }

                if (!Schema::hasColumn('booking_services', 'supplier_invoice_number')) {
                    $table->string('supplier_invoice_number')
                        ->nullable()
                        ->after('supplier_id');
                }
            });
        }

        if (Schema::hasTable('receipts') && !Schema::hasColumn('receipts', 'booking_service_id')) {
            Schema::table('receipts', function (Blueprint $table) {
                $table->foreignId('booking_service_id')
                    ->nullable()
                    ->after('booking_id')
                    ->constrained('booking_services')
                    ->nullOnDelete();

                $table->unique('booking_service_id');
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('receipts') && Schema::hasColumn('receipts', 'booking_service_id')) {
            Schema::table('receipts', function (Blueprint $table) {
                $table->dropUnique(['booking_service_id']);
                $table->dropConstrainedForeignId('booking_service_id');
            });
        }

        if (Schema::hasTable('booking_services')) {
            Schema::table('booking_services', function (Blueprint $table) {
                if (Schema::hasColumn('booking_services', 'supplier_invoice_number')) {
                    $table->dropColumn('supplier_invoice_number');
                }

                if (Schema::hasColumn('booking_services', 'supplier_id')) {
                    $table->dropConstrainedForeignId('supplier_id');
                }
            });
        }
    }
};
