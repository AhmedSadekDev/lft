<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\AgentExpense;
use App\Models\MoneyTransfer;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Running custody statement for an agent (compute-on-read).
 * Expense rows reduce the balance only after wallet debit / approval.
 */
class AgentCustodyStatementService
{
    /**
     * @return array{
     *     opening_balance: float,
     *     closing_balance: float,
     *     rows: Collection<int, array{
     *         key: string,
     *         model: AgentExpense|MoneyTransfer,
     *         effective_at: Carbon,
     *         delta: float,
     *         balance_before: float,
     *         balance_after: float
     *     }>,
     *     balances_by_key: array<string, array{balance_before: float, balance_after: float, delta: float}>
     * }
     */
    public function build(Agent $agent, ?string $from = null, ?string $to = null): array
    {
        $agentId = (int) $agent->id;
        $events = $this->collectEvents($agent, $agentId);

        $events = $events->sort(function (array $left, array $right) {
            $time = $left['effective_at']->timestamp <=> $right['effective_at']->timestamp;
            if ($time !== 0) {
                return $time;
            }

            return strcmp($left['key'], $right['key']);
        })->values();

        $fromBound = $from ? Carbon::parse($from)->startOfDay() : null;
        $toBound = $to ? Carbon::parse($to)->endOfDay() : null;

        $balance = 0.0;
        $opening = 0.0;
        $rows = collect();

        foreach ($events as $event) {
            /** @var Carbon $at */
            $at = $event['effective_at'];
            $delta = (float) $event['delta'];

            if ($fromBound && $at->lt($fromBound)) {
                $balance = round($balance + $delta, 2);
                $opening = $balance;
                continue;
            }

            if ($toBound && $at->gt($toBound)) {
                continue;
            }

            $before = $balance;
            $after = round($balance + $delta, 2);
            $rows->push([
                'key' => $event['key'],
                'model' => $event['model'],
                'effective_at' => $at,
                'delta' => $delta,
                'balance_before' => $before,
                'balance_after' => $after,
            ]);
            $balance = $after;
        }

        if (! $fromBound) {
            $opening = 0.0;
        }

        $balancesByKey = [];
        foreach ($rows as $row) {
            $balancesByKey[$row['key']] = [
                'balance_before' => $row['balance_before'],
                'balance_after' => $row['balance_after'],
                'delta' => $row['delta'],
            ];
        }

        return [
            'opening_balance' => round($opening, 2),
            'closing_balance' => round($balance, 2),
            'rows' => $rows,
            'balances_by_key' => $balancesByKey,
        ];
    }

    /**
     * @return Collection<int, array{key: string, model: AgentExpense|MoneyTransfer, effective_at: Carbon, delta: float}>
     */
    private function collectEvents(Agent $agent, int $agentId): Collection
    {
        $incoming = $agent->financial_custodies()->with(['transferer', 'transfered'])->get();
        $outgoing = $agent->sended_financial_custodies()->with(['transferer', 'transfered'])->get();
        $transfers = $incoming->concat($outgoing)->unique('id')->values();

        $events = collect();

        foreach ($transfers as $transfer) {
            /** @var MoneyTransfer $transfer */
            $delta = $this->transferDelta($transfer, $agentId);
            $events->push([
                'key' => 'transfer:' . $transfer->id,
                'model' => $transfer,
                'effective_at' => $this->modelTimestamp($transfer, 'created_at'),
                'delta' => $delta,
            ]);
        }

        foreach ($agent->expenses()->with(['agent', 'booking'])->get() as $expense) {
            /** @var AgentExpense $expense */
            $debited = $this->expenseAffectsWallet($expense);
            $effectiveAt = $debited
                ? $this->expenseEffectiveAt($expense)
                : $this->modelTimestamp($expense, 'created_at');

            $events->push([
                'key' => 'expense:' . $expense->id,
                'model' => $expense,
                'effective_at' => $effectiveAt,
                'delta' => $debited ? -round((float) $expense->value, 2) : 0.0,
            ]);
        }

        return $events;
    }

    private function transferDelta(MoneyTransfer $transfer, int $agentId): float
    {
        $value = round((float) $transfer->value, 2);
        $type = (int) $transfer->type;
        $isRecipient = $transfer->transfered_type === Agent::class && (int) $transfer->transfered_id === $agentId;
        $isSender = $transfer->transferer_type === Agent::class && (int) $transfer->transferer_id === $agentId;

        return match ($type) {
            MoneyTransfer::fromDashboard => $isRecipient ? $value : 0.0,
            MoneyTransfer::transferAgent => $isRecipient ? $value : ($isSender ? -$value : 0.0),
            // Wallet already reduced on request; approved is the same row after type change.
            MoneyTransfer::vaultReturnPending,
            MoneyTransfer::vaultReturnApproved => $isSender ? -$value : 0.0,
            // Type 3 full value out + type 5 commission back ≈ net wallet deduction.
            MoneyTransfer::deliveryPolicy => $isSender ? -$value : 0.0,
            MoneyTransfer::officeCommission => $isRecipient ? $value : 0.0,
            MoneyTransfer::settle,
            MoneyTransfer::carPayment,
            MoneyTransfer::supplierPayment => 0.0,
            default => 0.0,
        };
    }

    private function expenseAffectsWallet(AgentExpense $expense): bool
    {
        if ($expense->delivery_policy_id) {
            return false;
        }

        if ($expense->wallet_debited_at) {
            return true;
        }

        // Legacy approved app receipts that never stamped wallet_debited_at.
        return (int) $expense->admin_approval === 1;
    }

    private function expenseEffectiveAt(AgentExpense $expense): Carbon
    {
        if ($expense->wallet_debited_at) {
            return Carbon::parse($expense->getRawOriginal('wallet_debited_at') ?? $expense->wallet_debited_at);
        }

        if ($expense->reviewed_at) {
            return Carbon::parse($expense->getRawOriginal('reviewed_at') ?? $expense->reviewed_at);
        }

        return $this->modelTimestamp($expense, 'created_at');
    }

    private function modelTimestamp(AgentExpense|MoneyTransfer $model, string $column): Carbon
    {
        $raw = method_exists($model, 'getRawOriginal')
            ? $model->getRawOriginal($column)
            : ($model->{$column} ?? null);

        return $raw ? Carbon::parse($raw) : Carbon::createFromTimestamp(0);
    }
}
