<?php
require __DIR__.'/phase4a-bootstrap.php';

use App\Models\Booking;
use App\Models\BookingContainer;
use App\Models\Agent;
use App\Models\Superagent;
use App\Models\Car;
use App\Models\Driver;
use App\Models\Company;
use App\Models\Vault;
use App\Models\AgentExpense;
use App\Models\MoneyTransfer;
use App\Models\Payingcar;
use App\Models\DeliveryPolicy;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\VaultTransaction;
use App\Models\BankTrnsaction;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

echo "=== BENCHMARKING DASHBOARD OPTIMIZATION ===\n\n";

$hasVaults = Schema::hasTable('vaults');
$hasVaultTx = Schema::hasTable('vault_transactions');

// 1. LEGACY COMPUTATION (with vault safety guard to prevent fatal error on missing table)
function getLegacyDashboardData($hasVaults, $hasVaultTx) {
    $t0 = hrtime(true);
    $qCount = 0;
    
    // bookings
    $total_bookings = Booking::count();
    $today_bookings = Booking::whereDate('created_at', today())->count();
    $week_bookings = Booking::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count();
    $month_bookings = Booking::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count();

    // containers
    $total_containers = BookingContainer::count();
    $today_containers = BookingContainer::whereDate('created_at', today())->count();

    // users
    $total_agents = Agent::count();
    $total_superagents = Superagent::count();
    $total_companies = Company::count();

    // cars/drivers
    $total_cars = Car::count();
    $total_drivers = Driver::count();

    // financial
    $vault_amount = $hasVaults ? (Vault::first()->amount ?? 0) : 0;

    // today expenses
    $ae_today = AgentExpense::whereDate('created_at', today())->sum('value');
    $dp_today = MoneyTransfer::where('type', MoneyTransfer::deliveryPolicy)->whereDate('created_at', today())->sum('value');
    $se_today = MoneyTransfer::where('type', MoneyTransfer::settle)->whereDate('created_at', today())->sum('value');
    $ta_today = MoneyTransfer::where('type', MoneyTransfer::transferAgent)->whereDate('created_at', today())->sum('value');
    $pc_today = Payingcar::whereDate('created_at', today())->sum('value');
    $vt_today = $hasVaultTx ? VaultTransaction::where('type', 0)->whereDate('created_at', today())->sum('amount') : 0;
    $bt_today = BankTrnsaction::where('type', 0)->whereDate('created_at', today())->sum('amount');
    $today_expenses = $ae_today + $dp_today + $se_today + $ta_today + $pc_today + $vt_today + $bt_today;

    // today income
    $oc_today = MoneyTransfer::where('type', MoneyTransfer::officeCommission)->whereDate('created_at', today())->sum('value');
    $fd_today = MoneyTransfer::where('type', MoneyTransfer::fromDashboard)->whereDate('created_at', today())->sum('value');
    $ip_today = InvoicePayment::where(function($query) {
            $query->where('payment_type', '!=', 'check')
                  ->orWhere(function($q) {
                      $q->where('payment_type', 'check')->whereNotNull('check_paid_at');
                  });
        })->whereDate('created_at', today())->sum('value');
    $vti_today = $hasVaultTx ? VaultTransaction::where('type', 1)->whereDate('created_at', today())->sum('amount') : 0;
    $bti_today = BankTrnsaction::where('type', 1)->whereDate('created_at', today())->sum('amount');
    $today_income = $oc_today + $fd_today + $ip_today + $vti_today + $bti_today;

    // month expenses
    $ae_month = AgentExpense::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->sum('value');
    $dp_month = MoneyTransfer::where('type', MoneyTransfer::deliveryPolicy)->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->sum('value');
    $se_month = MoneyTransfer::where('type', MoneyTransfer::settle)->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->sum('value');
    $ta_month = MoneyTransfer::where('type', MoneyTransfer::transferAgent)->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->sum('value');
    $pc_month = Payingcar::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->sum('value');
    $vt_month = $hasVaultTx ? VaultTransaction::where('type', 0)->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->sum('amount') : 0;
    $bt_month = BankTrnsaction::where('type', 0)->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->sum('amount');
    $month_expenses = $ae_month + $dp_month + $se_month + $ta_month + $pc_month + $vt_month + $bt_month;

    // month income
    $oc_month = MoneyTransfer::where('type', MoneyTransfer::officeCommission)->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->sum('value');
    $fd_month = MoneyTransfer::where('type', MoneyTransfer::fromDashboard)->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->sum('value');
    $ip_month = InvoicePayment::where(function($query) {
            $query->where('payment_type', '!=', 'check')
                  ->orWhere(function($q) {
                      $q->where('payment_type', 'check')->whereNotNull('check_paid_at');
                  });
        })->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->sum('value');
    $vti_month = $hasVaultTx ? VaultTransaction::where('type', 1)->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->sum('amount') : 0;
    $bti_month = BankTrnsaction::where('type', 1)->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->sum('amount');
    $month_income = $oc_month + $fd_month + $ip_month + $vti_month + $bti_month;

    // policies
    $total_delivery_policies = DeliveryPolicy::count();
    $today_delivery_policies = DeliveryPolicy::whereDate('created_at', today())->count();

    // invoices
    $total_invoices = Invoice::count();
    $today_invoices = Invoice::whereDate('created_at', today())->count();
    $month_invoices = Invoice::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count();

    // checks
    $checks_due_within_3_days = InvoicePayment::where('payment_type', 'check')
        ->whereNull('check_paid_at')
        ->whereNotNull('check_due_date')
        ->whereBetween('check_due_date', [now()->startOfDay()->toDateString(), now()->addDays(3)->endOfDay()->toDateString()])
        ->count();

    // bookings chart (6 queries)
    $bMonths = [];
    $bData = [];
    for ($i = 5; $i >= 0; $i--) {
        $date = now()->subMonths($i);
        $bMonths[] = $date->format('M Y');
        $bData[] = Booking::whereMonth('created_at', $date->month)->whereYear('created_at', $date->year)->count();
    }

    // financial chart (72 queries)
    $fMonths = [];
    $fExpenses = [];
    $fIncome = [];
    for ($i = 5; $i >= 0; $i--) {
        $date = now()->subMonths($i);
        $fMonths[] = $date->format('M Y');

        $me = AgentExpense::whereMonth('created_at', $date->month)->whereYear('created_at', $date->year)->sum('value');
        $me += MoneyTransfer::where('type', MoneyTransfer::deliveryPolicy)->whereMonth('created_at', $date->month)->whereYear('created_at', $date->year)->sum('value');
        $me += MoneyTransfer::where('type', MoneyTransfer::settle)->whereMonth('created_at', $date->month)->whereYear('created_at', $date->year)->sum('value');
        $me += MoneyTransfer::where('type', MoneyTransfer::transferAgent)->whereMonth('created_at', $date->month)->whereYear('created_at', $date->year)->sum('value');
        $me += Payingcar::whereMonth('created_at', $date->month)->whereYear('created_at', $date->year)->sum('value');
        $me += $hasVaultTx ? VaultTransaction::where('type', 0)->whereMonth('created_at', $date->month)->whereYear('created_at', $date->year)->sum('amount') : 0;
        $me += BankTrnsaction::where('type', 0)->whereMonth('created_at', $date->month)->whereYear('created_at', $date->year)->sum('amount');
        $fExpenses[] = $me;

        $mi = MoneyTransfer::where('type', MoneyTransfer::officeCommission)->whereMonth('created_at', $date->month)->whereYear('created_at', $date->year)->sum('value');
        $mi += MoneyTransfer::where('type', MoneyTransfer::fromDashboard)->whereMonth('created_at', $date->month)->whereYear('created_at', $date->year)->sum('value');
        $mi += InvoicePayment::where(function($query) {
                $query->where('payment_type', '!=', 'check')
                      ->orWhere(function($q) {
                          $q->where('payment_type', 'check')->whereNotNull('check_paid_at');
                      });
            })->whereMonth('created_at', $date->month)->whereYear('created_at', $date->year)->sum('value');
        $mi += $hasVaultTx ? VaultTransaction::where('type', 1)->whereMonth('created_at', $date->month)->whereYear('created_at', $date->year)->sum('amount') : 0;
        $mi += BankTrnsaction::where('type', 1)->whereMonth('created_at', $date->month)->whereYear('created_at', $date->year)->sum('amount');
        $fIncome[] = $mi;
    }

    $elapsedMs = (hrtime(true) - $t0) / 1e6;

    return [
        'stats' => [
            'total_bookings' => $total_bookings,
            'today_bookings' => $today_bookings,
            'week_bookings' => $week_bookings,
            'month_bookings' => $month_bookings,
            'total_containers' => $total_containers,
            'today_containers' => $today_containers,
            'total_agents' => $total_agents,
            'total_superagents' => $total_superagents,
            'total_companies' => $total_companies,
            'total_cars' => $total_cars,
            'total_drivers' => $total_drivers,
            'vault_amount' => $vault_amount,
            'today_expenses' => $today_expenses,
            'today_income' => $today_income,
            'month_expenses' => $month_expenses,
            'month_income' => $month_income,
            'total_delivery_policies' => $total_delivery_policies,
            'today_delivery_policies' => $today_delivery_policies,
            'total_invoices' => $total_invoices,
            'today_invoices' => $today_invoices,
            'month_invoices' => $month_invoices,
            'checks_due_within_3_days' => $checks_due_within_3_days,
        ],
        'bookingsChart' => ['labels' => $bMonths, 'data' => $bData],
        'financialChart' => ['labels' => $fMonths, 'expenses' => $fExpenses, 'income' => $fIncome],
        'time_ms' => $elapsedMs,
    ];
}

// 2. OPTIMIZED SQL-AGGREGATED COMPUTATION
function getOptimizedDashboardData($hasVaults, $hasVaultTx) {
    $t0 = hrtime(true);

    $todayStart = now()->startOfDay()->toDateTimeString();
    $todayEnd = now()->endOfDay()->toDateTimeString();
    $weekStart = now()->startOfWeek()->toDateTimeString();
    $weekEnd = now()->endOfWeek()->toDateTimeString();
    $monthStart = now()->startOfMonth()->toDateTimeString();
    $monthEnd = now()->endOfMonth()->toDateTimeString();
    $sixMonthsAgo = now()->subMonths(5)->startOfMonth()->toDateTimeString();

    // 1 query for all booking stats
    $bAgg = Booking::selectRaw("
        COUNT(*) as total_count,
        COUNT(CASE WHEN created_at BETWEEN ? AND ? THEN 1 END) as today_count,
        COUNT(CASE WHEN created_at BETWEEN ? AND ? THEN 1 END) as week_count,
        COUNT(CASE WHEN created_at BETWEEN ? AND ? THEN 1 END) as month_count
    ", [$todayStart, $todayEnd, $weekStart, $weekEnd, $monthStart, $monthEnd])->first();

    // 1 query for booking containers
    $bcAgg = BookingContainer::selectRaw("
        COUNT(*) as total_count,
        COUNT(CASE WHEN created_at BETWEEN ? AND ? THEN 1 END) as today_count
    ", [$todayStart, $todayEnd])->first();

    // Entity counts
    $total_agents = Agent::count();
    $total_superagents = Superagent::count();
    $total_companies = Company::count();
    $total_cars = Car::count();
    $total_drivers = Driver::count();
    $vault_amount = $hasVaults ? (Vault::first()->amount ?? 0) : 0;

    // 1 query for delivery policies
    $dpAgg = DeliveryPolicy::selectRaw("
        COUNT(*) as total_count,
        COUNT(CASE WHEN created_at BETWEEN ? AND ? THEN 1 END) as today_count
    ", [$todayStart, $todayEnd])->first();

    // 1 query for invoices
    $invAgg = Invoice::selectRaw("
        COUNT(*) as total_count,
        COUNT(CASE WHEN created_at BETWEEN ? AND ? THEN 1 END) as today_count,
        COUNT(CASE WHEN created_at BETWEEN ? AND ? THEN 1 END) as month_count
    ", [$todayStart, $todayEnd, $monthStart, $monthEnd])->first();

    // 1 query for checks
    $checks_due_within_3_days = InvoicePayment::where('payment_type', 'check')
        ->whereNull('check_paid_at')
        ->whereNotNull('check_due_date')
        ->whereBetween('check_due_date', [now()->startOfDay()->toDateString(), now()->addDays(3)->endOfDay()->toDateString()])
        ->count();

    // 1 query for AgentExpense today & month
    $aeAgg = AgentExpense::where('created_at', '>=', $monthStart)
        ->selectRaw("
            COALESCE(SUM(CASE WHEN created_at BETWEEN ? AND ? THEN value END), 0) as today_val,
            COALESCE(SUM(value), 0) as month_val
        ", [$todayStart, $todayEnd])->first();

    // 1 query for MoneyTransfer by type for today & month
    $mtAgg = MoneyTransfer::where('created_at', '>=', $monthStart)
        ->selectRaw("
            type,
            COALESCE(SUM(CASE WHEN created_at BETWEEN ? AND ? THEN value END), 0) as today_val,
            COALESCE(SUM(value), 0) as month_val
        ", [$todayStart, $todayEnd])
        ->groupBy('type')
        ->get()
        ->keyBy('type');

    $dp_today = (float)($mtAgg[MoneyTransfer::deliveryPolicy]->today_val ?? 0);
    $dp_month = (float)($mtAgg[MoneyTransfer::deliveryPolicy]->month_val ?? 0);
    $se_today = (float)($mtAgg[MoneyTransfer::settle]->today_val ?? 0);
    $se_month = (float)($mtAgg[MoneyTransfer::settle]->month_val ?? 0);
    $ta_today = (float)($mtAgg[MoneyTransfer::transferAgent]->today_val ?? 0);
    $ta_month = (float)($mtAgg[MoneyTransfer::transferAgent]->month_val ?? 0);
    $oc_today = (float)($mtAgg[MoneyTransfer::officeCommission]->today_val ?? 0);
    $oc_month = (float)($mtAgg[MoneyTransfer::officeCommission]->month_val ?? 0);
    $fd_today = (float)($mtAgg[MoneyTransfer::fromDashboard]->today_val ?? 0);
    $fd_month = (float)($mtAgg[MoneyTransfer::fromDashboard]->month_val ?? 0);

    // 1 query for Payingcar today & month
    $pcAgg = Payingcar::where('created_at', '>=', $monthStart)
        ->selectRaw("
            COALESCE(SUM(CASE WHEN created_at BETWEEN ? AND ? THEN value END), 0) as today_val,
            COALESCE(SUM(value), 0) as month_val
        ", [$todayStart, $todayEnd])->first();

    // 1 query for BankTrnsaction today & month by type
    $btAgg = BankTrnsaction::where('created_at', '>=', $monthStart)
        ->selectRaw("
            type,
            COALESCE(SUM(CASE WHEN created_at BETWEEN ? AND ? THEN amount END), 0) as today_val,
            COALESCE(SUM(amount), 0) as month_val
        ", [$todayStart, $todayEnd])
        ->groupBy('type')
        ->get()
        ->keyBy('type');

    $bt_today = (float)($btAgg[0]->today_val ?? 0);
    $bt_month = (float)($btAgg[0]->month_val ?? 0);
    $bti_today = (float)($btAgg[1]->today_val ?? 0);
    $bti_month = (float)($btAgg[1]->month_val ?? 0);

    // VaultTransaction (guarded)
    $vt_today = 0; $vt_month = 0; $vti_today = 0; $vti_month = 0;
    if ($hasVaultTx) {
        $vtAgg = VaultTransaction::where('created_at', '>=', $monthStart)
            ->selectRaw("type, COALESCE(SUM(CASE WHEN created_at BETWEEN ? AND ? THEN amount END), 0) as today_val, COALESCE(SUM(amount), 0) as month_val", [$todayStart, $todayEnd])
            ->groupBy('type')->get()->keyBy('type');
        $vt_today = (float)($vtAgg[0]->today_val ?? 0);
        $vt_month = (float)($vtAgg[0]->month_val ?? 0);
        $vti_today = (float)($vtAgg[1]->today_val ?? 0);
        $vti_month = (float)($vtAgg[1]->month_val ?? 0);
    }

    // 1 query for InvoicePayment today & month
    $ipAgg = InvoicePayment::where('created_at', '>=', $monthStart)
        ->where(function($query) {
            $query->where('payment_type', '!=', 'check')
                  ->orWhere(function($q) {
                      $q->where('payment_type', 'check')->whereNotNull('check_paid_at');
                  });
        })
        ->selectRaw("
            COALESCE(SUM(CASE WHEN created_at BETWEEN ? AND ? THEN value END), 0) as today_val,
            COALESCE(SUM(value), 0) as month_val
        ", [$todayStart, $todayEnd])->first();

    $today_expenses = (float)($aeAgg->today_val ?? 0) + $dp_today + $se_today + $ta_today + (float)($pcAgg->today_val ?? 0) + $vt_today + $bt_today;
    $today_income = $oc_today + $fd_today + (float)($ipAgg->today_val ?? 0) + $vti_today + $bti_today;

    $month_expenses = (float)($aeAgg->month_val ?? 0) + $dp_month + $se_month + $ta_month + (float)($pcAgg->month_val ?? 0) + $vt_month + $bt_month;
    $month_income = $oc_month + $fd_month + (float)($ipAgg->month_val ?? 0) + $vti_month + $bti_month;

    // Bookings Chart (1 query)
    $bChartRaw = Booking::where('created_at', '>=', $sixMonthsAgo)
        ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym, COUNT(*) as cnt")
        ->groupBy('ym')
        ->pluck('cnt', 'ym');

    $bMonths = []; $bData = [];
    for ($i = 5; $i >= 0; $i--) {
        $date = now()->subMonths($i);
        $bMonths[] = $date->format('M Y');
        $bData[] = (int)($bChartRaw[$date->format('Y-m')] ?? 0);
    }

    // Financial Chart (5 grouped queries for the 6 months instead of 72 queries)
    $aeChart = AgentExpense::where('created_at', '>=', $sixMonthsAgo)
        ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym, SUM(value) as val")->groupBy('ym')->pluck('val', 'ym');

    $mtChart = MoneyTransfer::where('created_at', '>=', $sixMonthsAgo)
        ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym, type, SUM(value) as val")->groupBy('ym', 'type')
        ->get();
    $mtChartGrouped = [];
    foreach ($mtChart as $row) {
        $mtChartGrouped[$row->ym][$row->type] = (float)$row->val;
    }

    $pcChart = Payingcar::where('created_at', '>=', $sixMonthsAgo)
        ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym, SUM(value) as val")->groupBy('ym')->pluck('val', 'ym');

    $btChart = BankTrnsaction::where('created_at', '>=', $sixMonthsAgo)
        ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym, type, SUM(amount) as val")->groupBy('ym', 'type')
        ->get();
    $btChartGrouped = [];
    foreach ($btChart as $row) {
        $btChartGrouped[$row->ym][$row->type] = (float)$row->val;
    }

    $ipChart = InvoicePayment::where('created_at', '>=', $sixMonthsAgo)
        ->where(function($query) {
            $query->where('payment_type', '!=', 'check')->orWhere(function($q) {
                $q->where('payment_type', 'check')->whereNotNull('check_paid_at');
            });
        })
        ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym, SUM(value) as val")->groupBy('ym')->pluck('val', 'ym');

    $fMonths = []; $fExpenses = []; $fIncome = [];
    for ($i = 5; $i >= 0; $i--) {
        $date = now()->subMonths($i);
        $ym = $date->format('Y-m');
        $fMonths[] = $date->format('M Y');

        $me = (float)($aeChart[$ym] ?? 0);
        $me += (float)($mtChartGrouped[$ym][MoneyTransfer::deliveryPolicy] ?? 0);
        $me += (float)($mtChartGrouped[$ym][MoneyTransfer::settle] ?? 0);
        $me += (float)($mtChartGrouped[$ym][MoneyTransfer::transferAgent] ?? 0);
        $me += (float)($pcChart[$ym] ?? 0);
        $me += (float)($btChartGrouped[$ym][0] ?? 0);
        $fExpenses[] = $me;

        $mi = (float)($mtChartGrouped[$ym][MoneyTransfer::officeCommission] ?? 0);
        $mi += (float)($mtChartGrouped[$ym][MoneyTransfer::fromDashboard] ?? 0);
        $mi += (float)($ipChart[$ym] ?? 0);
        $mi += (float)($btChartGrouped[$ym][1] ?? 0);
        $fIncome[] = $mi;
    }

    $elapsedMs = (hrtime(true) - $t0) / 1e6;

    return [
        'stats' => [
            'total_bookings' => (int)$bAgg->total_count,
            'today_bookings' => (int)$bAgg->today_count,
            'week_bookings' => (int)$bAgg->week_count,
            'month_bookings' => (int)$bAgg->month_count,
            'total_containers' => (int)$bcAgg->total_count,
            'today_containers' => (int)$bcAgg->today_count,
            'total_agents' => $total_agents,
            'total_superagents' => $total_superagents,
            'total_companies' => $total_companies,
            'total_cars' => $total_cars,
            'total_drivers' => $total_drivers,
            'vault_amount' => $vault_amount,
            'today_expenses' => $today_expenses,
            'today_income' => $today_income,
            'month_expenses' => $month_expenses,
            'month_income' => $month_income,
            'total_delivery_policies' => (int)$dpAgg->total_count,
            'today_delivery_policies' => (int)$dpAgg->today_count,
            'total_invoices' => (int)$invAgg->total_count,
            'today_invoices' => (int)$invAgg->today_count,
            'month_invoices' => (int)$invAgg->month_count,
            'checks_due_within_3_days' => $checks_due_within_3_days,
        ],
        'bookingsChart' => ['labels' => $bMonths, 'data' => $bData],
        'financialChart' => ['labels' => $fMonths, 'expenses' => $fExpenses, 'income' => $fIncome],
        'time_ms' => $elapsedMs,
    ];
}

// Compare query counts
$qLegacy = [];
DB::listen(function($q) use (&$qLegacy) { $qLegacy[] = $q->sql; });
$legData = getLegacyDashboardData($hasVaults, $hasVaultTx);
$legacyCount = count($qLegacy);

$qOpt = [];
DB::listen(function($q) use (&$qOpt) { $qOpt[] = $q->sql; });
$optData = getOptimizedDashboardData($hasVaults, $hasVaultTx);
$optCount = count($qOpt);

echo "Legacy queries: $legacyCount | Time: " . round($legData['time_ms'], 2) . " ms\n";
echo "Optimized queries: $optCount | Time: " . round($optData['time_ms'], 2) . " ms\n";
echo "Query reduction: " . ($legacyCount - $optCount) . " queries (" . round((($legacyCount - $optCount)/$legacyCount)*100, 1) . "%)\n\n";

// Compare stats
$statsMatch = ($legData['stats'] === $optData['stats']);
echo "Stats equivalence: " . ($statsMatch ? "100% IDENTICAL" : "MISMATCH") . "\n";
if (!$statsMatch) {
    foreach ($legData['stats'] as $k => $v) {
        if ($v !== $optData['stats'][$k]) {
            echo "  Mismatch on $k: legacy=" . json_encode($v) . " vs opt=" . json_encode($optData['stats'][$k]) . "\n";
        }
    }
}

// Compare bookingsChart
$bcMatch = ($legData['bookingsChart'] === $optData['bookingsChart']);
echo "Bookings chart equivalence: " . ($bcMatch ? "100% IDENTICAL" : "MISMATCH") . "\n";

// Compare financialChart
$fcMatch = ($legData['financialChart'] === $optData['financialChart']);
echo "Financial chart equivalence: " . ($fcMatch ? "100% IDENTICAL" : "MISMATCH") . "\n";
if (!$fcMatch) {
    echo "Legacy FC: " . json_encode($legData['financialChart']) . "\n";
    echo "Opt FC:    " . json_encode($optData['financialChart']) . "\n";
}
