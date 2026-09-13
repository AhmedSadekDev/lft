<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\AgentExpense;
use App\Models\BookingContainer;
use Illuminate\Support\Facades\DB;

class StageExpenseService
{
    private function stage(?int $containerId, ?int $type, int $agentId, bool $asAdmin = false)
    {
        if (! $containerId) {
            return null;
        }
        abort_if($type === null, 422, 'يجب تحديد مرحلة الإيصال');
        $container = BookingContainer::lockForUpdate()->findOrFail($containerId);
        if ($asAdmin) {
            $stage = app(ContainerStageService::class)->receipts($container, $type);
            abort_if($stage->receipts_closed_at, 409, 'تم إقفال إيصالات المرحلة');

            return $stage;
        }

        return app(ContainerStageService::class)->assertReceiptsOpen($container, $type, $agentId);
    }

    public function create(int $agentId, array $data, string $requestKey, string $fingerprint, callable $storeImage): AgentExpense
    {
        return DB::transaction(function () use ($agentId, $data, $requestKey, $fingerprint, $storeImage) {
            // Take the container before the wallet lock, consistently with edits/deletes.
            $container = ! empty($data['booking_container_id']) ? BookingContainer::lockForUpdate()->findOrFail($data['booking_container_id']) : null;
            $agent = Agent::lockForUpdate()->findOrFail($agentId);
            $existing = AgentExpense::withTrashed()->where('agent_id', $agentId)->where('request_key', $requestKey)->lockForUpdate()->first();
            if ($existing) {
                abort_unless(hash_equals($existing->request_fingerprint, $fingerprint), 409, 'تم استخدام معرف الإرسال لبيانات مختلفة');

                return $existing;
            }
            $stage = $this->stage($container?->id, isset($data['type_id']) ? (int) $data['type_id'] : null, $agentId);
            $value = round((float) $data['value'], 2);
            abort_unless($value > 0 && $agent->wallet >= $value, 422, 'قيمة المصروف غير صحيحة أو الرصيد غير كافٍ');
            $data['image_agent_expenses'] = $storeImage();
            $data['value'] = $value;
            $expense = AgentExpense::create($data + ['agent_id' => $agentId, 'request_key' => $requestKey, 'request_fingerprint' => $fingerprint]);
            $agent->decrement('wallet', $value);
            if ($stage) {
                $stage->increment('version');
            }

            return $expense;
        });
    }

    public function change(int $agentId, int $expenseId, int $version, ?array $data, ?callable $storeImage = null, bool $asAdmin = false): void
    {
        DB::transaction(function () use ($agentId, $expenseId, $version, $data, $storeImage, $asAdmin) {
            $snapshot = AgentExpense::withTrashed()->findOrFail($expenseId);
            abort_unless((int) $snapshot->agent_id === $agentId, 403, 'غير مسموح بتعديل هذا الإيصال');
            $stage = $snapshot->type_id === null ? null : $this->stage($snapshot->booking_container_id, (int) $snapshot->type_id, $agentId, $asAdmin);
            $agent = Agent::lockForUpdate()->find($agentId);
            abort_unless($agent || $asAdmin, 404, 'المندوب غير موجود');
            $expense = AgentExpense::withTrashed()->lockForUpdate()->findOrFail($expenseId);
            abort_unless((int) $expense->agent_id === $agentId && $expense->booking_container_id == $snapshot->booking_container_id && $expense->type_id == $snapshot->type_id, 409, 'تغير الإيصال؛ حدث البيانات');
            abort_unless((int) $expense->version === $version, 409, 'تم تعديل الإيصال؛ حدث البيانات قبل المحاولة مجدداً');
            abort_if($expense->voided_at, 409, 'تم إلغاء الإيصال');
            abort_if($expense->admin_approval && ! $asAdmin, 409, 'الإيصال معتمد؛ يحتاج التصحيح بواسطة المسؤول');
            $funds = $agent;
            $balanceColumn = 'wallet';
            if ($expense->delivery_policy_id) {
                $policy = \App\Models\DeliveryPolicy::lockForUpdate()->findOrFail($expense->delivery_policy_id);
                abort_if($policy->is_settled, 409, 'تمت تسوية العهدة');
                $funds = $policy->money_transfer()->lockForUpdate()->firstOrFail();
                $balanceColumn = 'value';
            }
            if ($data === null) {
                if ($funds) {
                    $funds->increment($balanceColumn, $expense->value);
                }
                // Retain the request key as a tombstone: a delayed retry must not recreate a deleted expense.
                $expense->update(['version' => $expense->version + 1]);
                $expense->delete();
            } else {
                abort_if($expense->voided_at, 409, 'تم إلغاء الإيصال');
                $data['value'] = round((float) $data['value'], 2);
                abort_unless($data['value'] > 0, 422, 'قيمة المصروف غير صحيحة');
                $difference = round((float) $data['value'] - (float) $expense->value, 2);
                abort_if($funds && $difference > $funds->$balanceColumn, 422, 'الرصيد غير كافٍ');
                if ($storeImage) {
                    $data['image_agent_expenses'] = $storeImage();
                }
                $expense->update($data + ['version' => $expense->version + 1]);
                if ($funds && $difference != 0) {
                    $funds->decrement($balanceColumn, $difference);
                }
            }
            if ($stage) {
                $stage->increment('version');
            }
        });
    }
}
