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
        Schema::table('invoice_payments', function (Blueprint $table) {
            $table->enum('payment_type', ['bank_transfer', 'check'])->nullable()->after('value');
            $table->string('check_bank_name')->nullable()->after('payment_type');
            $table->string('check_number')->nullable()->after('check_bank_name');
            $table->date('check_due_date')->nullable()->after('check_number');
            $table->timestamp('check_paid_at')->nullable()->after('check_due_date');
            $table->text('notes')->nullable()->after('check_paid_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('invoice_payments', function (Blueprint $table) {
            $table->dropColumn(['payment_type', 'check_bank_name', 'check_number', 'check_due_date', 'check_paid_at', 'notes']);
        });
    }
};
