<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('service_categories', 'invoice_print_section')) {
            Schema::table('service_categories', function (Blueprint $table) {
                $table->string('invoice_print_section', 20)
                    ->nullable()
                    ->after('service_status');
            });
        }

        // Backfill from legacy service_status (0 taxed, 1/2 additional)
        DB::table('service_categories')->where('service_status', 0)->update(['invoice_print_section' => 'tax']);
        DB::table('service_categories')->whereIn('service_status', [1, 2])->update(['invoice_print_section' => 'additional']);
    }

    public function down(): void
    {
        if (Schema::hasColumn('service_categories', 'invoice_print_section')) {
            Schema::table('service_categories', function (Blueprint $table) {
                $table->dropColumn('invoice_print_section');
            });
        }
    }
};
