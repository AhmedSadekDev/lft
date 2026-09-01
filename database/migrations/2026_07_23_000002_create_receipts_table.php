<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * The receipts table did not exist in this codebase. It is created here with the
     * columns required for supplier statements (cost, booking/order link) plus the
     * supplier payment-source fields requested for the module.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('receipts')) {
            return;
        }

        Schema::create('receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->nullable()->constrained('bookings')->nullOnDelete();
            $table->decimal('cost', 15, 2)->default(0);
            $table->enum('payment_source', ['safe', 'representative', 'supplier'])->nullable();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->string('supplier_invoice_number')->nullable()->index();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('payment_source');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('receipts');
    }
};
