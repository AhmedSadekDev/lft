<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\MoneyTransfer;
use App\Models\Vault;
use App\Models\VaultTransaction;
use Illuminate\Support\Facades\DB;

/**
 * Agent → vault returns await accounting approval (MoneyTransfer type 6 → 9).
 * No schema change: pending/approved state is encoded in type only.
 */
class AgentVaultReturnService
{
    /**
     * Hold agent wallet and open a pending vault-return transfer. Vault is not credited yet.
     */
    public function request(Agent $agent, float $amount, ?string $notes = null): MoneyTransfer
    {
        return DB::transaction(function () use ($agent, $amount, $notes) {
            $agent = Agent::lockForUpdate()->findOrFail($agent->id);
            abort_unless((float) $agent->wallet >= $amount, 422, __('main.you dont have enougth money'));

            $agent->wallet = round((float) $agent->wallet - $amount, 2);
            $agent->save();

            return MoneyTransfer::create([
                'value' => $amount,
                'type' => MoneyTransfer::vaultReturnPending,
                'transferer_type' => Agent::class,
                'transferer_id' => $agent->id,
                'transfered_type' => null,
                'transfered_id' => null,
                'notes' => $notes,
            ]);
        });
    }

    public function approve(int $transferId): MoneyTransfer
    {
        return DB::transaction(function () use ($transferId) {
            $transfer = MoneyTransfer::lockForUpdate()->findOrFail($transferId);
            abort_unless((int) $transfer->type === MoneyTransfer::vaultReturnPending, 422, 'التحويل غير بانتظار المراجعة');
            abort_unless($transfer->transferer_type === Agent::class && $transfer->transferer_id, 422, 'المندوب غير معروف');

            $amount = round((float) $transfer->value, 2);
            $vault = Vault::lockForUpdate()->first();
            abort_unless($vault, 422, 'لا توجد خزنة في النظام');

            $vault->amount = round((float) $vault->amount + $amount, 2);
            $vault->save();

            $agent = Agent::find($transfer->transferer_id);
            VaultTransaction::create([
                'agient_id' => $transfer->transferer_id,
                'name' => 'تحويل الي الخزنه' . ($agent?->name ? ': ' . $agent->name : ''),
                'amount' => $amount,
                'type' => 1, // وارد للخزنة
            ]);

            $transfer->type = MoneyTransfer::vaultReturnApproved;
            $transfer->save();

            return $transfer;
        });
    }

    public function reject(int $transferId): void
    {
        DB::transaction(function () use ($transferId) {
            $transfer = MoneyTransfer::lockForUpdate()->findOrFail($transferId);
            abort_unless((int) $transfer->type === MoneyTransfer::vaultReturnPending, 422, 'التحويل غير بانتظار المراجعة');
            abort_unless($transfer->transferer_type === Agent::class && $transfer->transferer_id, 422, 'المندوب غير معروف');

            $amount = round((float) $transfer->value, 2);
            $agent = Agent::lockForUpdate()->findOrFail($transfer->transferer_id);
            $agent->wallet = round((float) $agent->wallet + $amount, 2);
            $agent->save();

            $transfer->delete();
        });
    }
}
