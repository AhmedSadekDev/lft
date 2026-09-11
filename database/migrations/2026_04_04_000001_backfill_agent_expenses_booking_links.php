<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ربط مصروفات الوكلاء القديمة بالحجز (كانت مفقودة من الفاتورة لأن العلاقة تعتمد على booking_id).
     */
    public function up(): void
    {
        if (! Schema::hasTable('agent_expenses')) {
            return;
        }

        // 1) تعبئة booking_id من الحاوية المرتبطة
        DB::statement('
            UPDATE agent_expenses e
            INNER JOIN booking_containers c ON c.id = e.booking_container_id
            SET e.booking_id = c.booking_id
            WHERE e.booking_container_id IS NOT NULL
              AND (e.booking_id IS NULL OR e.booking_id <> c.booking_id)
        ');

        // 2) مصروفات مرتبطة ببوليصة توصيل فقط: أول حاوية من delivery_policy_containers + booking_id
        if (Schema::hasTable('delivery_policy_containers') && Schema::hasTable('booking_containers')) {
            DB::statement('
                UPDATE agent_expenses e
                INNER JOIN (
                    SELECT dpc.delivery_policy_id, MIN(dpc.booking_container_id) AS booking_container_id
                    FROM delivery_policy_containers dpc
                    GROUP BY dpc.delivery_policy_id
                ) AS first_pc ON first_pc.delivery_policy_id = e.delivery_policy_id
                INNER JOIN booking_containers c ON c.id = first_pc.booking_container_id
                SET e.booking_container_id = first_pc.booking_container_id,
                    e.booking_id = c.booking_id
                WHERE e.delivery_policy_id IS NOT NULL
                  AND (e.booking_id IS NULL OR e.booking_container_id IS NULL)
            ');
        }
    }

    public function down(): void
    {
        //
    }
};
