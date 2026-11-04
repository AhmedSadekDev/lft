<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\AppNotification;
use App\Models\Bank;
use App\Models\BankTrnsaction;
use App\Models\MoneyTransfer;
use App\Models\Vault;
use App\Models\VaultTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VaultSpendService
{
    public const ACTION_AGENT = 'agent';
    public const ACTION_BANK = 'bank';
    public const ACTION_EXPENSE = 'expense';

    public const EXPENSE_GENERAL = 'general';
    public const EXPENSE_SPECIAL = 'special';

    /**
     * @param  array{
     *     action: string,
     *     amount: float|int|string,
     *     agent_id?: int|null,
     *     bank_id?: int|null,
     *     name?: string|null,
     *     date?: string|null,
     *     reason?: string|null,
     *     expense_kind?: string|null
     * }  $data
     */
    public function spend(array $data, ?int $userId = null): void
    {
        $action = (string) ($data['action'] ?? '');
        $amount = round((float) ($data['amount'] ?? 0), 2);

        if ($amount <= 0) {
            throw ValidationException::withMessages(['amount' => 'المبلغ يجب أن يكون أكبر من صفر']);
        }

        match ($action) {
            self::ACTION_AGENT => $this->transferToAgent($amount, (int) ($data['agent_id'] ?? 0), $userId),
            self::ACTION_BANK => $this->transferToBank(
                $amount,
                (int) ($data['bank_id'] ?? 0),
                trim((string) ($data['name'] ?? '')),
                $data['date'] ?? null,
                $userId
            ),
            self::ACTION_EXPENSE => $this->storeExpense(
                $amount,
                trim((string) ($data['reason'] ?? '')),
                (string) ($data['expense_kind'] ?? self::EXPENSE_GENERAL)
            ),
            default => throw ValidationException::withMessages(['action' => 'نوع العملية غير صالح']),
        };
    }

    private function transferToAgent(float $amount, int $agentId, ?int $userId): void
    {
        if ($agentId <= 0) {
            throw ValidationException::withMessages(['agent_id' => 'اختر المندوب']);
        }

        DB::transaction(function () use ($amount, $agentId, $userId) {
            $vault = Vault::query()->lockForUpdate()->first();
            if (! $vault) {
                throw ValidationException::withMessages(['action' => 'لا توجد خزنة في النظام']);
            }

            if ((float) $vault->amount < $amount) {
                throw ValidationException::withMessages(['amount' => __('main.wallet_does_not_have_enough_amount')]);
            }

            $agent = Agent::query()->lockForUpdate()->find($agentId);
            if (! $agent) {
                throw ValidationException::withMessages(['agent_id' => 'المندوب غير موجود']);
            }

            $agent->update([
                'wallet' => (float) $agent->wallet + $amount,
                'total_wallet' => (float) $agent->total_wallet + $amount,
            ]);

            MoneyTransfer::create([
                'value' => $amount,
                'transfered_type' => Agent::class,
                'transfered_id' => $agent->id,
                'transferer_type' => 'App\Models\User',
                'transferer_id' => $userId,
                'type' => MoneyTransfer::fromDashboard,
            ]);

            $vault->amount = round((float) $vault->amount - $amount, 2);
            $vault->save();

            VaultTransaction::create([
                'name' => 'صرف عهدة للمندوب: ' . ($agent->name ?? ''),
                'amount' => $amount,
                'type' => 0,
                'agient_id' => $agent->id,
            ]);

            $title = __('new_notification');
            $text = __('daily_financial_custody_added', [
                'value' => $amount,
                'agent' => $agent->name,
            ]);

            SaveNotification::create($title, $text, $agent->id, Agent::class, AppNotification::specific);
            SendNotification::send($agent->device_token ?? '', $title, $text);
        });
    }

    private function transferToBank(float $amount, int $bankId, string $name, ?string $date, ?int $userId): void
    {
        if ($bankId <= 0) {
            throw ValidationException::withMessages(['bank_id' => 'اختر البنك']);
        }

        if ($name === '') {
            throw ValidationException::withMessages(['name' => 'اسم العملية مطلوب']);
        }

        DB::transaction(function () use ($amount, $bankId, $name, $date, $userId) {
            $vault = Vault::query()->lockForUpdate()->first();
            if (! $vault) {
                throw ValidationException::withMessages(['action' => 'لا توجد خزنة في النظام']);
            }

            if ((float) $vault->amount < $amount) {
                throw ValidationException::withMessages(['amount' => __('main.vault_wallet_does_not_have_enough_amount')]);
            }

            $bank = Bank::query()->lockForUpdate()->find($bankId);
            if (! $bank) {
                throw ValidationException::withMessages(['bank_id' => 'البنك غير موجود']);
            }

            $vault->amount = round((float) $vault->amount - $amount, 2);
            $bank->amount = round((float) $bank->amount + $amount, 2);
            $vault->save();
            $bank->save();

            VaultTransaction::create([
                'bank_id' => $bank->id,
                'name' => $name,
                'amount' => $amount,
                'type' => 0,
            ]);

            BankTrnsaction::create([
                'bank_id' => $bank->id,
                'name' => $name,
                'date' => $date ?: now()->toDateString(),
                'amount' => $amount,
                'type' => 3,
                'user_id' => $userId,
            ]);
        });
    }

    private function storeExpense(float $amount, string $reason, string $expenseKind): void
    {
        if ($reason === '') {
            throw ValidationException::withMessages(['reason' => 'سبب المصروف مطلوب']);
        }

        if (! in_array($expenseKind, [self::EXPENSE_GENERAL, self::EXPENSE_SPECIAL], true)) {
            throw ValidationException::withMessages(['expense_kind' => 'نوع المصروف غير صالح']);
        }

        $prefix = $expenseKind === self::EXPENSE_SPECIAL ? 'مصروف خاص' : 'مصروف عام';
        $labeledReason = str_starts_with($reason, $prefix)
            ? $reason
            : $prefix . ': ' . $reason;

        app(DashboardExpenseService::class)->store([
            'value' => $amount,
            'reason' => $labeledReason,
            'source' => 'vault',
        ]);
    }
}
