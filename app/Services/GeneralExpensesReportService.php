<?php

namespace App\Services;

use App\Models\AgentExpense;
use App\Models\BankTrnsaction;
use App\Models\LogActivity;
use App\Models\MoneyTransfer;
use App\Models\Payingcar;
use App\Models\VaultTransaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class GeneralExpensesReportService
{
    public const KIND_ALL = 'all';
    public const KIND_AGENT = 'agent';
    public const KIND_DELIVERY = 'delivery';
    public const KIND_OFFICE = 'office';
    public const KIND_PAYING_CAR = 'paying_car';
    public const KIND_LOG = 'log';
    public const KIND_DASHBOARD = 'dashboard';

    /**
     * @return array{
     *     expenses: Collection,
     *     totals: array{expenses: float|int, income: float|int},
     *     from: string|null,
     *     to: string|null,
     *     is_default_range: bool,
     *     kind: string,
     *     awaitingReviewIds: \Illuminate\Support\Collection
     * }
     */
    public function build(Request $request): array
    {
        $range = $this->resolveDateRange($request);
        $kind = $this->resolveKind($request);

        $agentExpenses = $this->getAgentExpenses($range['from_bound'], $range['to_bound']);
        $deliveryPolicies = $this->getDeliveryPolicies($range['from_bound'], $range['to_bound']);
        $officeCommissions = $this->getOfficeCommissions($range['from_bound'], $range['to_bound']);
        $payingCars = $this->getPayingCars($range['from_bound'], $range['to_bound']);
        $logActivities = $this->getLogActivities($range['from_bound'], $range['to_bound']);
        $dashboardVaultExpenses = $this->getDashboardVaultExpenses($range['from_bound'], $range['to_bound']);
        $dashboardBankExpenses = $this->getDashboardBankExpenses($range['from_bound'], $range['to_bound']);

        $filteredLogActivities = $this->filterDuplicateLogActivities(
            $logActivities,
            $agentExpenses,
            $deliveryPolicies,
            $officeCommissions,
            $payingCars
        );

        $expenses = $agentExpenses
            ->concat($deliveryPolicies)
            ->concat($officeCommissions)
            ->concat($payingCars)
            ->concat($filteredLogActivities)
            ->concat($dashboardVaultExpenses)
            ->concat($dashboardBankExpenses)
            ->sortByDesc(fn ($item) => $this->sortTimestamp($item))
            ->values();

        if ($kind !== self::KIND_ALL) {
            $expenses = $expenses->filter(fn ($item) => $this->itemKind($item) === $kind)->values();
        }

        if ($request->filled('search')) {
            $expenses = $this->applySearch($expenses, $request->search)->values();
        }

        $totals = $this->calculateTotals($expenses);

        $awaitingReviewIds = $this->awaitingReviewIdsForRange($range['from_bound'], $range['to_bound']);

        return [
            'expenses' => $expenses,
            'totals' => $totals,
            'from' => $range['from'],
            'to' => $range['to'],
            'is_default_range' => $range['is_default'],
            'kind' => $kind,
            'awaitingReviewIds' => $awaitingReviewIds,
        ];
    }

    public function resolveKind(Request $request): string
    {
        $kind = (string) $request->input('kind', self::KIND_ALL);
        $allowed = [
            self::KIND_ALL,
            self::KIND_AGENT,
            self::KIND_DELIVERY,
            self::KIND_OFFICE,
            self::KIND_PAYING_CAR,
            self::KIND_LOG,
            self::KIND_DASHBOARD,
        ];

        return in_array($kind, $allowed, true) ? $kind : self::KIND_ALL;
    }

    public function itemKind($item): string
    {
        if ($item instanceof AgentExpense) {
            return self::KIND_AGENT;
        }
        if ($item instanceof MoneyTransfer) {
            return (int) $item->type === MoneyTransfer::officeCommission
                ? self::KIND_OFFICE
                : self::KIND_DELIVERY;
        }
        if ($item instanceof Payingcar) {
            return self::KIND_PAYING_CAR;
        }
        if ($item instanceof LogActivity) {
            return self::KIND_LOG;
        }
        if (
            ($item instanceof VaultTransaction && (int) $item->type === VaultTransaction::dashboardExpense)
            || ($item instanceof BankTrnsaction && (int) $item->type === BankTrnsaction::dashboardExpense)
        ) {
            return self::KIND_DASHBOARD;
        }

        return self::KIND_ALL;
    }

    public function paginate(Collection $expenses, Request $request): LengthAwarePaginator
    {
        $perPage = max(1, min(100, (int) $request->get('per_page', 15)));
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $currentItems = $expenses->slice(($currentPage - 1) * $perPage, $perPage)->values();

        return new LengthAwarePaginator(
            $currentItems,
            $expenses->count(),
            $perPage,
            $currentPage,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );
    }

    /**
     * @return array{
     *     from: string|null,
     *     to: string|null,
     *     from_bound: string|null,
     *     to_bound: string|null,
     *     is_default: bool
     * }
     */
    public function resolveDateRange(Request $request): array
    {
        $isDefault = ! $request->filled('from') && ! $request->filled('to');

        if ($isDefault) {
            $from = now()->startOfMonth()->toDateString();
            $to = now()->toDateString();
        } else {
            $from = $request->filled('from') ? (string) $request->input('from') : null;
            $to = $request->filled('to') ? (string) $request->input('to') : null;
        }

        return [
            'from' => $from,
            'to' => $to,
            'from_bound' => $from ? $from . ' 00:00:00' : null,
            'to_bound' => $to ? $to . ' 23:59:59' : null,
            'is_default' => $isDefault,
        ];
    }

    private function applyDateFilter($query, ?string $fromBound, ?string $toBound)
    {
        return $query
            ->when($fromBound, fn ($q) => $q->where('created_at', '>=', $fromBound))
            ->when($toBound, fn ($q) => $q->where('created_at', '<=', $toBound));
    }

    private function getAgentExpenses(?string $fromBound, ?string $toBound): Collection
    {
        $query = AgentExpense::with([
            'agent:id,name',
            'bookingService.agent:id,name',
            'bookingService.creator:id,name',
            'service.serviceCategory:id,title',
            'bookingContainer.booking:id,booking_number',
        ]);

        return $this->applyDateFilter($query, $fromBound, $toBound)
            ->latest('id')
            ->get();
    }

    private function getDeliveryPolicies(?string $fromBound, ?string $toBound): Collection
    {
        $query = MoneyTransfer::with([
            'transferer:id,name',
            'transfered:id,name',
            'delivery_policy.booking_containers.booking:id,booking_number',
            'delivery_policy.image',
        ])->where('type', MoneyTransfer::deliveryPolicy);

        return $this->applyDateFilter($query, $fromBound, $toBound)
            ->latest('id')
            ->get();
    }

    private function getOfficeCommissions(?string $fromBound, ?string $toBound): Collection
    {
        $query = MoneyTransfer::with([
            'transfered:id,name',
            'delivery_policy.booking_containers.booking:id,booking_number',
            'delivery_policy.image',
        ])->where('type', MoneyTransfer::officeCommission);

        return $this->applyDateFilter($query, $fromBound, $toBound)
            ->latest('id')
            ->get();
    }

    private function getPayingCars(?string $fromBound, ?string $toBound): Collection
    {
        $query = Payingcar::with([
            'car:id,car_number',
            'user:id,name',
            'delivery_policy.booking_containers.booking:id,booking_number',
        ]);

        return $this->applyDateFilter($query, $fromBound, $toBound)
            ->latest('id')
            ->get();
    }

    /**
     * Only MoneyTransfer activity types not already loaded as direct rows.
     */
    private function getLogActivities(?string $fromBound, ?string $toBound): Collection
    {
        $query = LogActivity::with(['attacher', 'log'])
            ->where('log_type', MoneyTransfer::class)
            ->whereHasMorph('log', [MoneyTransfer::class], function ($q) {
                $q->whereIn('type', [
                    MoneyTransfer::fromDashboard,
                    MoneyTransfer::transferAgent,
                    MoneyTransfer::settle,
                ]);
            });

        return $this->applyDateFilter($query, $fromBound, $toBound)
            ->latest('id')
            ->get();
    }

    private function getDashboardVaultExpenses(?string $fromBound, ?string $toBound): Collection
    {
        $query = VaultTransaction::query()
            ->where('type', VaultTransaction::dashboardExpense);

        return $this->applyDateFilter($query, $fromBound, $toBound)
            ->latest('id')
            ->get();
    }

    private function getDashboardBankExpenses(?string $fromBound, ?string $toBound): Collection
    {
        $query = BankTrnsaction::with(['bank:id,name', 'user:id,name'])
            ->where('type', BankTrnsaction::dashboardExpense);

        return $this->applyDateFilter($query, $fromBound, $toBound)
            ->latest('id')
            ->get();
    }

    private function filterDuplicateLogActivities(
        Collection $logActivities,
        Collection $agentExpenses,
        Collection $deliveryPolicies,
        Collection $officeCommissions,
        Collection $payingCars
    ): Collection {
        $existing = [];

        foreach ($agentExpenses as $expense) {
            $existing[AgentExpense::class . ':' . $expense->id] = true;
        }
        foreach ($deliveryPolicies as $transfer) {
            $existing[MoneyTransfer::class . ':' . $transfer->id] = true;
        }
        foreach ($officeCommissions as $transfer) {
            $existing[MoneyTransfer::class . ':' . $transfer->id] = true;
        }
        foreach ($payingCars as $payment) {
            $existing[Payingcar::class . ':' . $payment->id] = true;
        }

        return $logActivities->reject(function ($logActivity) use ($existing) {
            $key = $logActivity->log_type . ':' . $logActivity->log_id;

            return isset($existing[$key]);
        })->values();
    }

    private function awaitingReviewIdsForRange(?string $fromBound, ?string $toBound): Collection
    {
        return $this->applyDateFilter(AgentExpense::awaitingReview(), $fromBound, $toBound)
            ->pluck('id')
            ->flip();
    }

    private function applySearch(Collection $expenses, string $search): Collection
    {
        $searchLower = mb_strtolower($search, 'UTF-8');

        return $expenses->filter(function ($item) use ($searchLower) {
            $data = $this->extractSearchableData($item);

            return mb_strpos(mb_strtolower($data['agentName'], 'UTF-8'), $searchLower) !== false
                || mb_strpos(mb_strtolower($data['bookingNumber'], 'UTF-8'), $searchLower) !== false
                || mb_strpos(mb_strtolower($data['service'], 'UTF-8'), $searchLower) !== false;
        });
    }

    private function extractSearchableData($item): array
    {
        $data = ['agentName' => '', 'bookingNumber' => '', 'service' => ''];

        if ($item instanceof LogActivity) {
            $data['agentName'] = $item->attacher?->name ?? '';
            $log = $item->log;
            if ($log instanceof MoneyTransfer && $log->delivery_policy) {
                $firstContainer = $log->delivery_policy->booking_containers->first();
                $data['bookingNumber'] = $firstContainer->booking?->booking_number ?? '';
            }
            $data['service'] = (string) ($item->action ?? '');
        } elseif ($item instanceof MoneyTransfer) {
            $data['agentName'] = trim(($item->transferer?->name ?? '') . ' ' . ($item->transfered?->name ?? ''));
            if ($item->delivery_policy) {
                $firstContainer = $item->delivery_policy->booking_containers->first();
                $data['bookingNumber'] = $firstContainer->booking?->booking_number ?? '';
            }
        } elseif ($item instanceof AgentExpense) {
            $data['agentName'] = $item->responsible_name ?? '';
            $data['bookingNumber'] = $item->bookingContainer?->booking?->booking_number ?? '';
            if ($item->service) {
                $data['service'] = trim(($item->service->serviceCategory?->title ?? '') . ' ' . ($item->service->name ?? ''));
            }
        } elseif ($item instanceof Payingcar) {
            $data['agentName'] = $item->user?->name ?? '';
            if ($item->delivery_policy) {
                $firstContainer = $item->delivery_policy->booking_containers->first();
                $data['bookingNumber'] = $firstContainer->booking?->booking_number ?? '';
            }
            $data['service'] = 'سداد مديونية السيارة';
        } elseif ($item instanceof VaultTransaction) {
            $data['agentName'] = 'الخزنة';
            $data['service'] = (string) ($item->name ?? '');
        } elseif ($item instanceof BankTrnsaction) {
            $data['agentName'] = $item->bank?->name ?? ($item->user?->name ?? '');
            $data['service'] = (string) ($item->name ?? '');
        }

        return $data;
    }

    private function calculateTotals(Collection $expenses): array
    {
        $totals = ['expenses' => 0, 'income' => 0];

        foreach ($expenses as $item) {
            $values = $this->extractValues($item);
            $totals['expenses'] += $values['expense'];
            $totals['income'] += $values['income'];
        }

        return $totals;
    }

    private function extractValues($item): array
    {
        $values = ['expense' => 0, 'income' => 0];

        if ($item instanceof LogActivity) {
            $log = $item->log;
            $value = $log?->value ?? 0;

            if ($item->log_type === AgentExpense::class) {
                $values['expense'] = $value;
            } elseif ($item->log_type === MoneyTransfer::class && $log) {
                $type = (int) $log->type;
                if (in_array($type, [
                    MoneyTransfer::deliveryPolicy,
                    MoneyTransfer::transferAgent,
                    MoneyTransfer::settle,
                ], true)) {
                    $values['expense'] = $value;
                } elseif (in_array($type, [
                    MoneyTransfer::officeCommission,
                    MoneyTransfer::fromDashboard,
                ], true)) {
                    $values['income'] = $value;
                }
            }
        } elseif ($item instanceof MoneyTransfer) {
            $value = $item->value ?? 0;
            if ((int) $item->type === MoneyTransfer::deliveryPolicy) {
                $values['expense'] = $value;
            } elseif ((int) $item->type === MoneyTransfer::officeCommission) {
                $values['income'] = $value;
            }
        } elseif ($item instanceof AgentExpense) {
            $values['expense'] = $item->value ?? 0;
        } elseif ($item instanceof Payingcar) {
            $values['expense'] = (float) ($item->value ?? 0);
        } elseif (
            ($item instanceof VaultTransaction && (int) $item->type === VaultTransaction::dashboardExpense)
            || ($item instanceof BankTrnsaction && (int) $item->type === BankTrnsaction::dashboardExpense)
        ) {
            $values['expense'] = (float) ($item->amount ?? 0);
        }

        return $values;
    }

    private function sortTimestamp($item): int
    {
        if ($item instanceof AgentExpense) {
            $raw = $item->getRawOriginal('created_at');

            return $raw ? (int) Carbon::parse($raw)->timestamp : 0;
        }

        $created = $item->created_at ?? null;
        if (! $created) {
            return 0;
        }

        return $created instanceof Carbon
            ? $created->timestamp
            : (int) Carbon::parse((string) $created)->timestamp;
    }
}
