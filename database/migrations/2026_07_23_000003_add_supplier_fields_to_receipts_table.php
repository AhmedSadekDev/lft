<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds supplier-related columns when receipts already exists without them
     * (e.g. created outside this migration set). Safe to re-run alongside
     * create_receipts_table which already includes these columns.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('receipts')) {
            return;
        }

        Schema::table('receipts', function (Blueprint $table) {
            if (!Schema::hasColumn('receipts', 'payment_source')) {
                $table->enum('payment_source', ['safe', 'representative', 'supplier'])->nullable()->after('cost');
            }

            if (!Schema::hasColumn('receipts', 'supplier_id')) {
                $table->foreignId('supplier_id')->nullable()->after('payment_source')->constrained('suppliers')->nullOnDelete();
            }

            if (!Schema::hasColumn('receipts', 'supplier_invoice_number')) {
                $table->string('supplier_invoice_number')->nullable()->after('supplier_id')->index();
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (!Schema::hasTable('receipts')) {
            return;
        }

        Schema::table('receipts', function (Blueprint $table) {
            if (Schema::hasColumn('receipts', 'supplier_id')) {
                $table->dropConstrainedForeignId('supplier_id');
            }

            if (Schema::hasColumn('receipts', 'payment_source')) {
                $table->dropColumn('payment_source');
            }

            if (Schema::hasColumn('receipts', 'supplier_invoice_number')) {
                $table->dropColumn('supplier_invoice_number');
            }
        });
    }
};
