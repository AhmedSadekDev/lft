<?php

namespace App\Services;

use App\Models\Bank;
use App\Models\BankTrnsaction;
use App\Models\Company;
use App\Models\InvoicePayment;
use App\Models\Vault;
use App\Models\VaultTransaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Edits and deletions on the bank screen reverse their effect on the other side of the
 * movement (vault, receiving bank, company statement). A movement whose other side cannot
 * be identified is rejected instead of being removed while the balances keep its effect.
 */
class BankTransactionService
{
    public const VAULT_WITHDRAWAL = 'vault_withdrawal';
    public const TRANSFER = 'transfer';
    public const COMPANY_PAYMENT = 'company_payment';
    public const OPENING_BALANCE_PAYMENT = 'opening_balance_payment';

    public function delete(int $id): void
    {
        DB::transaction(function () use ($id) {
            $transaction = BankTrnsaction::lockForUpdate()->findOrFail($id);
            [$kind, $vaultTransaction] = $this->resolve($transaction);
            abort_if($kind === null, 422, $this->unsupportedMessage($transaction));

            $amount = (float) $transaction->amount;
            $bank = Bank::lockForUpdate()->findOrFail($transaction->bank_id);

            switch ($kind) {
                case self::VAULT_WITHDRAWAL:
                    $vault = Vault::lockForUpdate()->firstOrFail();
                    $this->adjust($vault, -$amount, 'رصيد الخزنة لا يكفي لإلغاء السحب');
                    $this->adjust($bank, $amount);
                    $vaultTransaction->delete();
                    break;

                case self::TRANSFER:
                    $receiver = Bank::lockForUpdate()->findOrFail($transaction->trans_bank_id);
                    $this->adjust($receiver, -$amount, 'رصيد البنك المحوَّل إليه لا يكفي لإلغاء التحويل');
                    $this->adjust($bank, $amount);
                    break;

                case self::COMPANY_PAYMENT:
                    $this->adjust($bank, -$amount, 'رصيد البنك لا يكفي لإلغاء السداد');
                    // The invoices become due again, as when the payment is deleted from the company statement.
                    InvoicePayment::where('bank_transaction_id', $transaction->id)->delete();
                    break;

                case self::OPENING_BALANCE_PAYMENT:
                    $this->adjust($bank, -$amount, 'رصيد البنك لا يكفي لإلغاء السداد');
                    $company = Company::lockForUpdate()->findOrFail($transaction->company_id);
                    $company->opening_balance = round((float) $company->opening_balance + $amount, 2);
                    $company->save();
                    break;
            }

            $transaction->delete();
        });
    }

    /**
     * @param  array{name: string, date: ?string, amount: float|string, image?: string}  $data
     */
    public function update(int $id, array $data): BankTrnsaction
    {
        return DB::transaction(function () use ($id, $data) {
            $transaction = BankTrnsaction::lockForUpdate()->findOrFail($id);
            [$kind, $vaultTransaction] = $this->resolve($transaction);

            $newAmount = round((float) $data['amount'], 2);
            $delta = round($newAmount - (float) $transaction->amount, 2);

            if ($delta != 0) {
                abort_unless(in_array($kind, [self::VAULT_WITHDRAWAL, self::TRANSFER], true), 422, $kind === null
                    ? $this->unsupportedMessage($transaction)
                    : 'مبلغ سداد الشركة يُعدَّل من كشف حساب الشركة حتى تبقى الفواتير والبنك متطابقة.');

                $bank = Bank::lockForUpdate()->findOrFail($transaction->bank_id);
                if ($kind === self::VAULT_WITHDRAWAL) {
                    $vault = Vault::lockForUpdate()->firstOrFail();
                    $this->adjust($bank, -$delta, 'رصيد البنك لا يكفي');
                    $this->adjust($vault, $delta, 'رصيد الخزنة لا يكفي لتقليل مبلغ السحب');
                } else {
                    $receiver = Bank::lockForUpdate()->findOrFail($transaction->trans_bank_id);
                    $this->adjust($bank, -$delta, 'رصيد البنك لا يكفي');
                    $this->adjust($receiver, $delta, 'رصيد البنك المحوَّل إليه لا يكفي لتقليل مبلغ التحويل');
                }
            }

            if ($vaultTransaction) {
                $vaultTransaction->update(['name' => $data['name'], 'amount' => $newAmount]);
            }

            $transaction->fill([
                'name' => $data['name'],
                'date' => $data['date'] ?? null,
                'amount' => $newAmount,
            ] + (isset($data['image']) ? ['image' => $data['image']] : []));
            $transaction->save();

            return $transaction;
        });
    }

    /**
     * @return array{0: ?string, 1: ?VaultTransaction}
     */
    private function resolve(BankTrnsaction $transaction): array
    {
        $type = (int) $transaction->type;

        if ($type === 2 && $transaction->trans_bank_id) {
            return [self::TRANSFER, null];
        }

        if ($type === 0 && ! $transaction->company_id) {
            $vaultTransaction = $this->linkedVaultTransaction($transaction);

            return $vaultTransaction ? [self::VAULT_WITHDRAWAL, $vaultTransaction] : [null, null];
        }

        if ($type === 1 && $transaction->company_id) {
            if (str_contains((string) $transaction->name, 'سداد الرصيد الافتتاحي')) {
                return [self::OPENING_BALANCE_PAYMENT, null];
            }
            $linkedTotal = (float) InvoicePayment::where('bank_transaction_id', $transaction->id)
                ->lockForUpdate()->get()->sum(fn ($payment) => (float) $payment->value);
            if ($linkedTotal > 0 && abs($linkedTotal - (float) $transaction->amount) < 0.01) {
                return [self::COMPANY_PAYMENT, null];
            }
        }

        return [null, null];
    }

    /**
     * A bank-to-vault withdrawal and its vault entry are written in the same request, but no
     * column links them; the entry is the vault row of the same bank written at the same moment.
     */
    private function linkedVaultTransaction(BankTrnsaction $transaction): ?VaultTransaction
    {
        if (! $transaction->created_at) {
            return null;
        }

        $createdAt = $transaction->created_at;

        return VaultTransaction::where('bank_id', $transaction->bank_id)
            ->whereNull('agient_id')
            ->where('type', 0)
            ->whereBetween('created_at', [$createdAt->copy()->subSeconds(5), $createdAt->copy()->addSeconds(5)])
            ->lockForUpdate()
            ->get()
            ->sortBy(fn ($row) => [
                abs((float) $row->amount - (float) $transaction->amount) < 0.01 ? 0 : 1,
                abs($row->created_at->diffInSeconds($createdAt, false)),
            ])
            ->first();
    }

    private function unsupportedMessage(BankTrnsaction $transaction): string
    {
        if ($transaction->company_id) {
            return 'هذا السداد غير مربوط بفواتير محددة؛ احذفه أو عدّله من كشف حساب الشركة.';
        }
        if (str_contains((string) $transaction->name, 'طلب رقم')) {
            return 'هذه الحركة دفعة خدمة على طلب؛ عدّلها أو احذفها من صفحة الطلب حتى لا يختل حساب الطلب.';
        }

        return 'تعذّر تحديد الطرف الآخر لهذه الحركة (خزنة/بنك/شركة) لعكس أثرها تلقائياً؛ يلزم تصحيح الأرصدة يدوياً.';
    }

    private function adjust(Model $account, float $delta, string $insufficientMessage = ''): void
    {
        $balance = round((float) $account->amount + $delta, 2);
        abort_if($delta < 0 && $balance < 0, 422, $insufficientMessage);
        $account->amount = $balance;
        $account->save();
    }
}
