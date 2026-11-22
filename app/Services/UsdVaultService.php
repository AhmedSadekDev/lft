<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\Bank;
use App\Models\UsdVault;
use App\Models\UsdVaultTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UsdVaultService
{
    public function ensureVault(): UsdVault
    {
        $vault = UsdVault::primary();
        if ($vault) {
            return $vault;
        }

        return UsdVault::create([
            'name' => 'خزنة الدولار',
            'opening_balance' => 0,
            'balance' => 0,
            'is_active' => true,
        ]);
    }

    /**
     * @param  array{
     *     direction: string,
     *     category: string,
     *     amount: float|int|string,
     *     occurred_at?: string|null,
     *     reference?: string|null,
     *     description?: string|null,
     *     agent_id?: int|null,
     *     bank_id?: int|null,
     *     delivery_policy_id?: int|null,
     *     idempotency_key?: string|null
     * }  $data
     */
    public function post(array $data, ?int $userId = null): UsdVaultTransaction
    {
        $direction = (string) ($data['direction'] ?? '');
        $category = (string) ($data['category'] ?? '');
        $amount = round((float) ($data['amount'] ?? 0), 2);

        if (! in_array($direction, [UsdVaultTransaction::DIR_IN, UsdVaultTransaction::DIR_OUT], true)) {
            throw ValidationException::withMessages(['direction' => 'اتجاه الحركة غير صالح']);
        }
        if ($amount <= 0) {
            throw ValidationException::withMessages(['amount' => 'المبلغ يجب أن يكون أكبر من صفر']);
        }
        if ($category === '') {
            throw ValidationException::withMessages(['category' => 'التصنيف مطلوب']);
        }

        return DB::transaction(function () use ($data, $direction, $category, $amount, $userId) {
            $vault = UsdVault::query()->lockForUpdate()->find($this->ensureVault()->id);
            if (! $vault) {
                throw ValidationException::withMessages(['vault' => 'خزنة الدولار غير موجودة — طبّق SQL أولاً']);
            }

            if ($direction === UsdVaultTransaction::DIR_OUT && (float) $vault->balance < $amount) {
                throw ValidationException::withMessages(['amount' => 'رصيد خزنة الدولار غير كافٍ']);
            }

            $agentId = isset($data['agent_id']) ? (int) $data['agent_id'] : null;
            $bankId = isset($data['bank_id']) ? (int) $data['bank_id'] : null;

            if ($category === UsdVaultTransaction::CAT_AGENT_CUSTODY && $agentId) {
                $agent = Agent::query()->lockForUpdate()->find($agentId);
                if (! $agent) {
                    throw ValidationException::withMessages(['agent_id' => 'المندوب غير موجود']);
                }
                if ($direction === UsdVaultTransaction::DIR_OUT) {
                    $agent->wallet_usd = round((float) ($agent->wallet_usd ?? 0) + $amount, 2);
                } else {
                    if ((float) ($agent->wallet_usd ?? 0) < $amount) {
                        throw ValidationException::withMessages(['amount' => 'رصيد دولار المندوب غير كافٍ']);
                    }
                    $agent->wallet_usd = round((float) $agent->wallet_usd - $amount, 2);
                }
                $agent->save();
            }

            if ($category === UsdVaultTransaction::CAT_BANK_TRANSFER && $bankId) {
                $bank = Bank::query()->lockForUpdate()->find($bankId);
                if (! $bank) {
                    throw ValidationException::withMessages(['bank_id' => 'البنك غير موجود']);
                }
                if ($direction === UsdVaultTransaction::DIR_OUT) {
                    $bank->amount_usd = round((float) ($bank->amount_usd ?? 0) + $amount, 2);
                } else {
                    if ((float) ($bank->amount_usd ?? 0) < $amount) {
                        throw ValidationException::withMessages(['amount' => 'رصيد دولار البنك غير كافٍ']);
                    }
                    $bank->amount_usd = round((float) $bank->amount_usd - $amount, 2);
                }
                $bank->save();
            }

            $vault->balance = $direction === UsdVaultTransaction::DIR_IN
                ? round((float) $vault->balance + $amount, 2)
                : round((float) $vault->balance - $amount, 2);
            $vault->save();

            return UsdVaultTransaction::create([
                'usd_vault_id' => $vault->id,
                'direction' => $direction,
                'category' => $category,
                'amount' => $amount,
                'currency' => 'USD',
                'occurred_at' => $data['occurred_at'] ?? now(),
                'reference' => $data['reference'] ?? null,
                'description' => $data['description'] ?? null,
                'agent_id' => $agentId ?: null,
                'bank_id' => $bankId ?: null,
                'delivery_policy_id' => $data['delivery_policy_id'] ?? null,
                'created_by' => $userId,
                'idempotency_key' => $data['idempotency_key'] ?? null,
            ]);
        });
    }

    public function deposit(float $amount, ?string $description = null, ?string $reference = null, ?int $userId = null): UsdVaultTransaction
    {
        return $this->post([
            'direction' => UsdVaultTransaction::DIR_IN,
            'category' => UsdVaultTransaction::CAT_DEPOSIT,
            'amount' => $amount,
            'description' => $description,
            'reference' => $reference,
        ], $userId);
    }

    public function withdraw(float $amount, ?string $description = null, ?string $reference = null, ?int $userId = null): UsdVaultTransaction
    {
        return $this->post([
            'direction' => UsdVaultTransaction::DIR_OUT,
            'category' => UsdVaultTransaction::CAT_WITHDRAWAL,
            'amount' => $amount,
            'description' => $description,
            'reference' => $reference,
        ], $userId);
    }

    public function transferToAgent(float $amount, int $agentId, ?string $description = null, ?int $userId = null): UsdVaultTransaction
    {
        return $this->post([
            'direction' => UsdVaultTransaction::DIR_OUT,
            'category' => UsdVaultTransaction::CAT_AGENT_CUSTODY,
            'amount' => $amount,
            'agent_id' => $agentId,
            'description' => $description ?: 'صرف عهدة دولار لمندوب',
        ], $userId);
    }

    public function transferToBank(float $amount, int $bankId, ?string $description = null, ?int $userId = null): UsdVaultTransaction
    {
        return $this->post([
            'direction' => UsdVaultTransaction::DIR_OUT,
            'category' => UsdVaultTransaction::CAT_BANK_TRANSFER,
            'amount' => $amount,
            'bank_id' => $bankId,
            'description' => $description ?: 'تحويل دولار من الخزنة للبنك',
        ], $userId);
    }

    /**
     * Adjust agent USD wallet when delivery-policy custody_usd changes (dashboard).
     * Diff > 0 means more USD custody given → debit agent wallet_usd.
     */
    public function applyPolicyCustodyDiff(Agent $agent, float $diffUsd, int $deliveryPolicyId, ?int $userId = null): void
    {
        $diffUsd = round($diffUsd, 2);
        if ($diffUsd == 0.0) {
            return;
        }

        DB::transaction(function () use ($agent, $diffUsd, $deliveryPolicyId, $userId) {
            $locked = Agent::query()->lockForUpdate()->find($agent->id);
            if (! $locked) {
                throw ValidationException::withMessages(['agent_id' => 'المندوب غير موجود']);
            }

            if ($diffUsd > 0 && (float) ($locked->wallet_usd ?? 0) < $diffUsd) {
                throw ValidationException::withMessages(['custody_usd' => 'رصيد دولار المندوب غير كافٍ للعهدة']);
            }

            $locked->wallet_usd = round((float) ($locked->wallet_usd ?? 0) - $diffUsd, 2);
            $locked->save();

            $vault = UsdVault::query()->lockForUpdate()->find($this->ensureVault()->id);
            if ($vault) {
                // Policy custody is funded from agent USD wallet (already held), ledger only.
                UsdVaultTransaction::create([
                    'usd_vault_id' => $vault->id,
                    'direction' => $diffUsd > 0 ? UsdVaultTransaction::DIR_OUT : UsdVaultTransaction::DIR_IN,
                    'category' => UsdVaultTransaction::CAT_POLICY_CUSTODY,
                    'amount' => abs($diffUsd),
                    'currency' => 'USD',
                    'occurred_at' => now(),
                    'description' => 'تعديل عهدة دولار بوليصة #' . $deliveryPolicyId,
                    'agent_id' => $locked->id,
                    'delivery_policy_id' => $deliveryPolicyId,
                    'created_by' => $userId,
                ]);
            }
        });
    }
}
