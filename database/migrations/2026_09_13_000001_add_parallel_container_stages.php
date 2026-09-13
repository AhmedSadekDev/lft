<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('booking_container_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_container_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('type_id');
            $table->timestamp('receipts_closed_at')->nullable();
            $table->unsignedBigInteger('receipts_closed_by')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();
            $table->unique(['booking_container_id', 'type_id'], 'container_stage_unique');
        });
        Schema::table('booking_container_agents', function (Blueprint $table) {
            // Identity of the assignment, independent of the container's changing status.
            $table->unsignedTinyInteger('stage_type')->nullable()->index();
        });
        // Preserve only the phase represented by each surviving legacy assignment.
        // Deleted historical assignments cannot safely be reconstructed from global flags.
        DB::table('booking_container_agents')->update(['stage_type' => DB::raw(
            'CASE WHEN COALESCE(superagent_specification_approved, 0) = 0 THEN 0 WHEN COALESCE(superagent_loading_approved, 0) = 0 THEN 1 ELSE 2 END'
        )]);
        Schema::table('agent_expenses', function (Blueprint $table) {
            $table->unsignedInteger('version')->default(1);
            $table->string('request_key', 80)->nullable();
            $table->string('request_fingerprint', 64)->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->unique(['agent_id', 'request_key'], 'agent_expense_request_unique');
        });
    }

    public function down()
    {
        // An old application cannot interpret parallel assignments or deletion tombstones.
        // Rolling back after use would revive cancelled expenses or discard closure records.
        if (DB::table('agent_expenses')->whereNotNull('request_key')->orWhereNotNull('voided_at')->exists()
            || DB::table('booking_container_stages')->exists()
            || DB::table('booking_container_agents')->select('booking_container_id')->groupBy('booking_container_id')->havingRaw('COUNT(DISTINCT stage_type) > 1')->exists()) {
            throw new RuntimeException('Parallel stages have been used. Reconcile data before rolling back this migration.');
        }
        Schema::table('agent_expenses', function (Blueprint $table) {
            $table->dropUnique('agent_expense_request_unique');
            $table->dropColumn(['version', 'request_key', 'request_fingerprint', 'voided_at']);
        });
        Schema::table('booking_container_agents', function (Blueprint $table) {
            $table->dropIndex(['stage_type']);
            $table->dropColumn('stage_type');
        });
        Schema::dropIfExists('booking_container_stages');
    }
};
