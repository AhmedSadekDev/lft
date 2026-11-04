<?php

namespace App\Services;

use App\Models\Bank;
use App\Models\BankTrnsaction;
use App\Models\Vault;
use App\Models\VaultTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DashboardExpenseService
{
    /**
     * @param  array{value: float|int|string, reason: string, source: string, bank_id?: int|null}  $data
     * @return VaultTransaction|BankTrnsaction
     */
    public function store(array $data, ?int $userId = null)
    {
        $value = round((float) $data['value'], 2);
        $reason = trim((string) $data['reason']);
        $source = (string) $data['source'];

        if ($value <= 0) {
            throw ValidationException::withMessages(['value' => 'قيمة المصروف يجب أن تكون أكبر من صفر']);
        }

        if ($reason === '') {
            throw ValidationException::withMessages(['reason' => 'سبب المصروف مطلوب']);
        }

        if (! in_array($source, ['vault', 'bank'], true)) {
            throw ValidationException::withMessages(['source' => 'مكان الخصم غير صالح']);
        }

        return DB::transaction(function () use ($value, $reason, $source, $data, $userId) {
            if ($source === 'vault') {
                return $this->debitVault($value, $reason);
            }

            return $this->debitBank($value, $reason, (int) ($data['bank_id'] ?? 0), $userId);
        });
    }

    private function debitVault(float $value, string $reason): VaultTransaction
    {
        $vault = Vault::query()->lockForUpdate()->first();
        if (! $vault) {
            throw ValidationException::withMessages(['source' => 'لا توجد خزنة في النظام']);
        }

        if ((float) $vault->amount < $value) {
            throw ValidationException::withMessages(['value' => __('main.vault_wallet_does_not_have_enough_amount')]);
        }

        $vault->amount = round((float) $vault->amount - $value, 2);
        $vault->save();

        return VaultTransaction::create([
            'bank_id' => null,
            'name' => $reason,
            'amount' => $value,
            'type' => VaultTransaction::dashboardExpense,
        ]);
    }

    private function debitBank(float $value, string $reason, int $bankId, ?int $userId): BankTrnsaction
    {
        if ($bankId <= 0) {
            throw ValidationException::withMessages(['bank_id' => 'اختر البنك']);
        }

        $bank = Bank::query()->lockForUpdate()->find($bankId);
        if (! $bank) {
            throw ValidationException::withMessages(['bank_id' => 'البنك غير موجود']);
        }

        if ((float) $bank->amount < $value) {
            throw ValidationException::withMessages(['value' => __('main.bank_wallet_does_not_have_enough_amount')]);
        }

        $bank->amount = round((float) $bank->amount - $value, 2);
        $bank->save();

        return BankTrnsaction::create([
            'bank_id' => $bank->id,
            'user_id' => $userId,
            'name' => $reason,
            'date' => now()->toDateString(),
            'amount' => $value,
            'type' => BankTrnsaction::dashboardExpense,
        ]);
    }
}
