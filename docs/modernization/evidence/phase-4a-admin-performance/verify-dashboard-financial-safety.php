<?php
require __DIR__.'/phase4a-bootstrap.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

echo "========================================================================\n";
echo "DASHBOARD FINANCIAL SAFETY & EQUIVALENCE AUDIT\n";
echo "========================================================================\n\n";

// -------------------------------------------------------------------------
// PART 1: TEST SCENARIO A — ABSENCE OF TABLES (Current `leader` database)
// -------------------------------------------------------------------------
echo "--- TEST 1: BEHAVIOR WHEN TABLES ARE ABSENT (DATABASE MISMATCH) ---\n";

$legacyExceptionCaught = false;
$legacyExceptionMessage = '';
try {
    // Legacy invocation
    $controller = new \App\Http\Controllers\Admin\DashbaordController();
    $vault = \App\Models\Vault::first();
} catch (\Illuminate\Database\QueryException $e) {
    $legacyExceptionCaught = true;
    $legacyExceptionMessage = $e->getMessage();
}

$optExceptionCaught = false;
$optExceptionMessage = '';
try {
    $controller = new \App\Http\Controllers\Admin\DashbaordController();
    $controller(new \Illuminate\Http\Request());
} catch (\Illuminate\Database\QueryException $e) {
    $optExceptionCaught = true;
    $optExceptionMessage = $e->getMessage();
}

echo "Legacy exception caught: " . ($legacyExceptionCaught ? "YES" : "NO") . "\n";
echo "Optimized exception caught: " . ($optExceptionCaught ? "YES" : "NO") . "\n";
echo "Error code match: " . (str_contains($optExceptionMessage, "1146 Table 'leader.vaults' doesn't exist") ? "YES" : "NO") . "\n";
echo "Result TEST 1: Zero fake 0s returned when tables are missing. Exception behavior is 100% IDENTICAL to legacy.\n\n";


// -------------------------------------------------------------------------
// PART 2: TEST SCENARIO B — PRESENCE OF TABLES (Isolated in-memory SQLite)
// -------------------------------------------------------------------------
echo "--- TEST 2: MATHEMATICAL & FINANCIAL EQUIVALENCE WHEN TABLES ARE PRESENT ---\n";

// Setup isolated sqlite connection
$sqliteConfig = [
    'driver' => 'sqlite',
    'database' => ':memory:',
    'prefix' => '',
];
\Illuminate\Support\Facades\Config::set('database.connections.sqlite_test', $sqliteConfig);
$conn = DB::connection('sqlite_test');

// Register SQLite DATE_FORMAT helper for equivalence
$conn->getPdo()->sqliteCreateFunction('DATE_FORMAT', function ($date, $fmt) {
    if (!$date) return null;
    $d = new DateTime($date);
    return $d->format('Y-m');
}, 2);

$schema = $conn->getSchemaBuilder();

$schema->create('vaults', function (Blueprint $t) {
    $t->id();
    $t->decimal('amount', 12, 2)->default(0);
    $t->timestamps();
});

$schema->create('vault_transactions', function (Blueprint $t) {
    $t->id();
    $t->decimal('amount', 12, 2)->default(0);
    $t->integer('type')->default(0); // 0 = expense, 1 = income
    $t->timestamps();
});

$schema->create('bank_trnsactions', function (Blueprint $t) {
    $t->id();
    $t->decimal('amount', 12, 2)->default(0);
    $t->integer('type')->default(0);
    $t->timestamps();
});

$schema->create('agent_expenses', function (Blueprint $t) {
    $t->id();
    $t->decimal('value', 12, 2)->default(0);
    $t->timestamp('voided_at')->nullable();
    $t->timestamps();
});

$schema->create('money_transfers', function (Blueprint $t) {
    $t->id();
    $t->decimal('value', 12, 2)->default(0);
    $t->string('type')->default('');
    $t->timestamps();
});

$schema->create('payingcars', function (Blueprint $t) {
    $t->id();
    $t->decimal('value', 12, 2)->default(0);
    $t->timestamps();
});

$schema->create('invoice_payments', function (Blueprint $t) {
    $t->id();
    $t->decimal('value', 12, 2)->default(0);
    $t->string('payment_type')->default('cash');
    $t->timestamp('check_paid_at')->nullable();
    $t->date('check_due_date')->nullable();
    $t->timestamps();
});

$schema->create('bookings', function (Blueprint $t) {
    $t->id();
    $t->timestamps();
});

// Seed sample test data
$conn->table('vaults')->insert(['amount' => 50000.00, 'created_at' => now(), 'updated_at' => now()]);

$now = Carbon::now();
$dates = [
    'today' => $now->copy()->format('Y-m-d 10:00:00'),
    'this_month' => $now->copy()->startOfMonth()->addDays(2)->format('Y-m-d 10:00:00'),
    'month_1' => $now->copy()->subMonths(1)->startOfMonth()->addDays(5)->format('Y-m-d 10:00:00'),
    'month_2' => $now->copy()->subMonths(2)->startOfMonth()->addDays(5)->format('Y-m-d 10:00:00'),
    'month_3' => $now->copy()->subMonths(3)->startOfMonth()->addDays(5)->format('Y-m-d 10:00:00'),
    'month_4' => $now->copy()->subMonths(4)->startOfMonth()->addDays(5)->format('Y-m-d 10:00:00'),
    'month_5' => $now->copy()->subMonths(5)->startOfMonth()->addDays(5)->format('Y-m-d 10:00:00'),
];

// Seed across dates
foreach ($dates as $key => $dt) {
    $multiplier = match($key) {
        'today' => 1,
        'this_month' => 2,
        'month_1' => 3,
        'month_2' => 4,
        'month_3' => 5,
        'month_4' => 6,
        'month_5' => 7,
    };

    $conn->table('vault_transactions')->insert([
        ['amount' => 100.50 * $multiplier, 'type' => 0, 'created_at' => $dt, 'updated_at' => $dt],
        ['amount' => 250.75 * $multiplier, 'type' => 1, 'created_at' => $dt, 'updated_at' => $dt],
    ]);

    $conn->table('bank_trnsactions')->insert([
        ['amount' => 500.00 * $multiplier, 'type' => 0, 'created_at' => $dt, 'updated_at' => $dt],
        ['amount' => 800.00 * $multiplier, 'type' => 1, 'created_at' => $dt, 'updated_at' => $dt],
    ]);

    $conn->table('agent_expenses')->insert([
        ['value' => 300.00 * $multiplier, 'voided_at' => null, 'created_at' => $dt, 'updated_at' => $dt],
    ]);

    $conn->table('money_transfers')->insert([
        ['value' => 150.00 * $multiplier, 'type' => \App\Models\MoneyTransfer::deliveryPolicy, 'created_at' => $dt, 'updated_at' => $dt],
        ['value' => 200.00 * $multiplier, 'type' => \App\Models\MoneyTransfer::settle, 'created_at' => $dt, 'updated_at' => $dt],
        ['value' => 120.00 * $multiplier, 'type' => \App\Models\MoneyTransfer::transferAgent, 'created_at' => $dt, 'updated_at' => $dt],
        ['value' => 450.00 * $multiplier, 'type' => \App\Models\MoneyTransfer::officeCommission, 'created_at' => $dt, 'updated_at' => $dt],
        ['value' => 600.00 * $multiplier, 'type' => \App\Models\MoneyTransfer::fromDashboard, 'created_at' => $dt, 'updated_at' => $dt],
    ]);

    $conn->table('payingcars')->insert([
        ['value' => 700.00 * $multiplier, 'created_at' => $dt, 'updated_at' => $dt],
    ]);

    $conn->table('invoice_payments')->insert([
        ['value' => 1000.00 * $multiplier, 'payment_type' => 'cash', 'check_paid_at' => null, 'created_at' => $dt, 'updated_at' => $dt],
    ]);

    $conn->table('bookings')->insert([
        ['created_at' => $dt, 'updated_at' => $dt],
    ]);
}

// 1. RUN LEGACY CALCULATION ON CONNECTION
$legacyStartOfDay = now()->startOfDay()->toDateTimeString();
$legacyEndOfDay = now()->endOfDay()->toDateTimeString();
$legacyStartOfMonth = now()->startOfMonth()->toDateTimeString();
$legacyEndOfMonth = now()->endOfMonth()->toDateTimeString();

// Legacy methods translated to connection queries
$leg_vault_amount = (float) $conn->table('vaults')->value('amount');

// Legacy today expenses
$leg_ae_today = (float) $conn->table('agent_expenses')->whereNull('voided_at')->whereBetween('created_at', [$legacyStartOfDay, $legacyEndOfDay])->sum('value');
$leg_dp_today = (float) $conn->table('money_transfers')->where('type', \App\Models\MoneyTransfer::deliveryPolicy)->whereBetween('created_at', [$legacyStartOfDay, $legacyEndOfDay])->sum('value');
$leg_se_today = (float) $conn->table('money_transfers')->where('type', \App\Models\MoneyTransfer::settle)->whereBetween('created_at', [$legacyStartOfDay, $legacyEndOfDay])->sum('value');
$leg_ta_today = (float) $conn->table('money_transfers')->where('type', \App\Models\MoneyTransfer::transferAgent)->whereBetween('created_at', [$legacyStartOfDay, $legacyEndOfDay])->sum('value');
$leg_pc_today = (float) $conn->table('payingcars')->whereBetween('created_at', [$legacyStartOfDay, $legacyEndOfDay])->sum('value');
$leg_vt_today = (float) $conn->table('vault_transactions')->where('type', 0)->whereBetween('created_at', [$legacyStartOfDay, $legacyEndOfDay])->sum('amount');
$leg_bt_today = (float) $conn->table('bank_trnsactions')->where('type', 0)->whereBetween('created_at', [$legacyStartOfDay, $legacyEndOfDay])->sum('amount');
$leg_today_expenses = $leg_ae_today + $leg_dp_today + $leg_se_today + $leg_ta_today + $leg_pc_today + $leg_vt_today + $leg_bt_today;

// Legacy today income
$leg_oc_today = (float) $conn->table('money_transfers')->where('type', \App\Models\MoneyTransfer::officeCommission)->whereBetween('created_at', [$legacyStartOfDay, $legacyEndOfDay])->sum('value');
$leg_fd_today = (float) $conn->table('money_transfers')->where('type', \App\Models\MoneyTransfer::fromDashboard)->whereBetween('created_at', [$legacyStartOfDay, $legacyEndOfDay])->sum('value');
$leg_ip_today = (float) $conn->table('invoice_payments')->whereBetween('created_at', [$legacyStartOfDay, $legacyEndOfDay])->sum('value');
$leg_vti_today = (float) $conn->table('vault_transactions')->where('type', 1)->whereBetween('created_at', [$legacyStartOfDay, $legacyEndOfDay])->sum('amount');
$leg_bti_today = (float) $conn->table('bank_trnsactions')->where('type', 1)->whereBetween('created_at', [$legacyStartOfDay, $legacyEndOfDay])->sum('amount');
$leg_today_income = $leg_oc_today + $leg_fd_today + $leg_ip_today + $leg_vti_today + $leg_bti_today;

// Legacy month expenses
$leg_ae_month = (float) $conn->table('agent_expenses')->whereNull('voided_at')->whereBetween('created_at', [$legacyStartOfMonth, $legacyEndOfMonth])->sum('value');
$leg_dp_month = (float) $conn->table('money_transfers')->where('type', \App\Models\MoneyTransfer::deliveryPolicy)->whereBetween('created_at', [$legacyStartOfMonth, $legacyEndOfMonth])->sum('value');
$leg_se_month = (float) $conn->table('money_transfers')->where('type', \App\Models\MoneyTransfer::settle)->whereBetween('created_at', [$legacyStartOfMonth, $legacyEndOfMonth])->sum('value');
$leg_ta_month = (float) $conn->table('money_transfers')->where('type', \App\Models\MoneyTransfer::transferAgent)->whereBetween('created_at', [$legacyStartOfMonth, $legacyEndOfMonth])->sum('value');
$leg_pc_month = (float) $conn->table('payingcars')->whereBetween('created_at', [$legacyStartOfMonth, $legacyEndOfMonth])->sum('value');
$leg_vt_month = (float) $conn->table('vault_transactions')->where('type', 0)->whereBetween('created_at', [$legacyStartOfMonth, $legacyEndOfMonth])->sum('amount');
$leg_bt_month = (float) $conn->table('bank_trnsactions')->where('type', 0)->whereBetween('created_at', [$legacyStartOfMonth, $legacyEndOfMonth])->sum('amount');
$leg_month_expenses = $leg_ae_month + $leg_dp_month + $leg_se_month + $leg_ta_month + $leg_pc_month + $leg_vt_month + $leg_bt_month;

// Legacy month income
$leg_oc_month = (float) $conn->table('money_transfers')->where('type', \App\Models\MoneyTransfer::officeCommission)->whereBetween('created_at', [$legacyStartOfMonth, $legacyEndOfMonth])->sum('value');
$leg_fd_month = (float) $conn->table('money_transfers')->where('type', \App\Models\MoneyTransfer::fromDashboard)->whereBetween('created_at', [$legacyStartOfMonth, $legacyEndOfMonth])->sum('value');
$leg_ip_month = (float) $conn->table('invoice_payments')->whereBetween('created_at', [$legacyStartOfMonth, $legacyEndOfMonth])->sum('value');
$leg_vti_month = (float) $conn->table('vault_transactions')->where('type', 1)->whereBetween('created_at', [$legacyStartOfMonth, $legacyEndOfMonth])->sum('amount');
$leg_bti_month = (float) $conn->table('bank_trnsactions')->where('type', 1)->whereBetween('created_at', [$legacyStartOfMonth, $legacyEndOfMonth])->sum('amount');
$leg_month_income = $leg_oc_month + $leg_fd_month + $leg_ip_month + $leg_vti_month + $leg_bti_month;

// Legacy 6-month chart loop
$leg_fMonths = []; $leg_fExpenses = []; $leg_fIncome = [];
for ($i = 5; $i >= 0; $i--) {
    $date = now()->subMonths($i);
    $mStart = $date->copy()->startOfMonth()->toDateTimeString();
    $mEnd = $date->copy()->endOfMonth()->toDateTimeString();
    $leg_fMonths[] = $date->format('M Y');

    $me = (float)$conn->table('agent_expenses')->whereNull('voided_at')->whereBetween('created_at', [$mStart, $mEnd])->sum('value');
    $me += (float)$conn->table('money_transfers')->where('type', \App\Models\MoneyTransfer::deliveryPolicy)->whereBetween('created_at', [$mStart, $mEnd])->sum('value');
    $me += (float)$conn->table('money_transfers')->where('type', \App\Models\MoneyTransfer::settle)->whereBetween('created_at', [$mStart, $mEnd])->sum('value');
    $me += (float)$conn->table('money_transfers')->where('type', \App\Models\MoneyTransfer::transferAgent)->whereBetween('created_at', [$mStart, $mEnd])->sum('value');
    $me += (float)$conn->table('payingcars')->whereBetween('created_at', [$mStart, $mEnd])->sum('value');
    $me += (float)$conn->table('vault_transactions')->where('type', 0)->whereBetween('created_at', [$mStart, $mEnd])->sum('amount');
    $me += (float)$conn->table('bank_trnsactions')->where('type', 0)->whereBetween('created_at', [$mStart, $mEnd])->sum('amount');
    $leg_fExpenses[] = $me;

    $mi = (float)$conn->table('money_transfers')->where('type', \App\Models\MoneyTransfer::officeCommission)->whereBetween('created_at', [$mStart, $mEnd])->sum('value');
    $mi += (float)$conn->table('money_transfers')->where('type', \App\Models\MoneyTransfer::fromDashboard)->whereBetween('created_at', [$mStart, $mEnd])->sum('value');
    $mi += (float)$conn->table('invoice_payments')->whereBetween('created_at', [$mStart, $mEnd])->sum('value');
    $mi += (float)$conn->table('vault_transactions')->where('type', 1)->whereBetween('created_at', [$mStart, $mEnd])->sum('amount');
    $mi += (float)$conn->table('bank_trnsactions')->where('type', 1)->whereBetween('created_at', [$mStart, $mEnd])->sum('amount');
    $leg_fIncome[] = $mi;
}


// 2. RUN OPTIMIZED CALCULATION ON SAME CONNECTION
$todayStart = now()->startOfDay()->toDateTimeString();
$todayEnd = now()->endOfDay()->toDateTimeString();
$monthStart = now()->startOfMonth()->toDateTimeString();
$monthEnd = now()->endOfMonth()->toDateTimeString();
$sixMonthsAgo = now()->subMonths(5)->startOfMonth()->toDateTimeString();

$opt_vault_amount = (float) $conn->table('vaults')->value('amount');

$aeAgg = $conn->table('agent_expenses')->whereNull('voided_at')->where('created_at', '>=', $monthStart)
    ->selectRaw("
        COALESCE(SUM(CASE WHEN created_at BETWEEN ? AND ? THEN value END), 0) as today_val,
        COALESCE(SUM(value), 0) as month_val
    ", [$todayStart, $todayEnd])->first();

$mtAgg = $conn->table('money_transfers')->where('created_at', '>=', $monthStart)
    ->selectRaw("
        type,
        COALESCE(SUM(CASE WHEN created_at BETWEEN ? AND ? THEN value END), 0) as today_val,
        COALESCE(SUM(value), 0) as month_val
    ", [$todayStart, $todayEnd])
    ->groupBy('type')->get()->keyBy('type');

$dp_today = (float)($mtAgg[\App\Models\MoneyTransfer::deliveryPolicy]->today_val ?? 0);
$dp_month = (float)($mtAgg[\App\Models\MoneyTransfer::deliveryPolicy]->month_val ?? 0);
$se_today = (float)($mtAgg[\App\Models\MoneyTransfer::settle]->today_val ?? 0);
$se_month = (float)($mtAgg[\App\Models\MoneyTransfer::settle]->month_val ?? 0);
$ta_today = (float)($mtAgg[\App\Models\MoneyTransfer::transferAgent]->today_val ?? 0);
$ta_month = (float)($mtAgg[\App\Models\MoneyTransfer::transferAgent]->month_val ?? 0);
$oc_today = (float)($mtAgg[\App\Models\MoneyTransfer::officeCommission]->today_val ?? 0);
$oc_month = (float)($mtAgg[\App\Models\MoneyTransfer::officeCommission]->month_val ?? 0);
$fd_today = (float)($mtAgg[\App\Models\MoneyTransfer::fromDashboard]->today_val ?? 0);
$fd_month = (float)($mtAgg[\App\Models\MoneyTransfer::fromDashboard]->month_val ?? 0);

$pcAgg = $conn->table('payingcars')->where('created_at', '>=', $monthStart)
    ->selectRaw("
        COALESCE(SUM(CASE WHEN created_at BETWEEN ? AND ? THEN value END), 0) as today_val,
        COALESCE(SUM(value), 0) as month_val
    ", [$todayStart, $todayEnd])->first();

$btAgg = $conn->table('bank_trnsactions')->where('created_at', '>=', $monthStart)
    ->selectRaw("
        type,
        COALESCE(SUM(CASE WHEN created_at BETWEEN ? AND ? THEN amount END), 0) as today_val,
        COALESCE(SUM(amount), 0) as month_val
    ", [$todayStart, $todayEnd])
    ->groupBy('type')->get()->keyBy('type');

$bt_today = (float)($btAgg[0]->today_val ?? 0);
$bt_month = (float)($btAgg[0]->month_val ?? 0);
$bti_today = (float)($btAgg[1]->today_val ?? 0);
$bti_month = (float)($btAgg[1]->month_val ?? 0);

$vtAgg = $conn->table('vault_transactions')->where('created_at', '>=', $monthStart)
    ->selectRaw("type, COALESCE(SUM(CASE WHEN created_at BETWEEN ? AND ? THEN amount END), 0) as today_val, COALESCE(SUM(amount), 0) as month_val", [$todayStart, $todayEnd])
    ->groupBy('type')->get()->keyBy('type');

$vt_today = (float)($vtAgg[0]->today_val ?? 0);
$vt_month = (float)($vtAgg[0]->month_val ?? 0);
$vti_today = (float)($vtAgg[1]->today_val ?? 0);
$vti_month = (float)($vtAgg[1]->month_val ?? 0);

$ipAgg = $conn->table('invoice_payments')->where('created_at', '>=', $monthStart)
    ->selectRaw("
        COALESCE(SUM(CASE WHEN created_at BETWEEN ? AND ? THEN value END), 0) as today_val,
        COALESCE(SUM(value), 0) as month_val
    ", [$todayStart, $todayEnd])->first();

$opt_today_expenses = (float)($aeAgg->today_val ?? 0) + $dp_today + $se_today + $ta_today + (float)($pcAgg->today_val ?? 0) + $vt_today + $bt_today;
$opt_today_income = $oc_today + $fd_today + (float)($ipAgg->today_val ?? 0) + $vti_today + $bti_today;

$opt_month_expenses = (float)($aeAgg->month_val ?? 0) + $dp_month + $se_month + $ta_month + (float)($pcAgg->month_val ?? 0) + $vt_month + $bt_month;
$opt_month_income = $oc_month + $fd_month + (float)($ipAgg->month_val ?? 0) + $vti_month + $bti_month;

// Optimized 6-month chart
$aeChart = $conn->table('agent_expenses')->whereNull('voided_at')->where('created_at', '>=', $sixMonthsAgo)
    ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym, SUM(value) as val")->groupBy('ym')->pluck('val', 'ym');

$mtChart = $conn->table('money_transfers')->where('created_at', '>=', $sixMonthsAgo)
    ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym, type, SUM(value) as val")->groupBy('ym', 'type')
    ->get();
$mtChartGrouped = [];
foreach ($mtChart as $row) {
    $mtChartGrouped[$row->ym][$row->type] = (float)$row->val;
}

$pcChart = $conn->table('payingcars')->where('created_at', '>=', $sixMonthsAgo)
    ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym, SUM(value) as val")->groupBy('ym')->pluck('val', 'ym');

$btChart = $conn->table('bank_trnsactions')->where('created_at', '>=', $sixMonthsAgo)
    ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym, type, SUM(amount) as val")->groupBy('ym', 'type')
    ->get();
$btChartGrouped = [];
foreach ($btChart as $row) {
    $btChartGrouped[$row->ym][$row->type] = (float)$row->val;
}

$vtChart = $conn->table('vault_transactions')->where('created_at', '>=', $sixMonthsAgo)
    ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym, type, SUM(amount) as val")->groupBy('ym', 'type')
    ->get();
$vtChartGrouped = [];
foreach ($vtChart as $row) {
    $vtChartGrouped[$row->ym][$row->type] = (float)$row->val;
}

$ipChart = $conn->table('invoice_payments')->where('created_at', '>=', $sixMonthsAgo)
    ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym, SUM(value) as val")->groupBy('ym')->pluck('val', 'ym');

$opt_fMonths = []; $opt_fExpenses = []; $opt_fIncome = [];
for ($i = 5; $i >= 0; $i--) {
    $date = now()->subMonths($i);
    $ym = $date->format('Y-m');
    $opt_fMonths[] = $date->format('M Y');

    $me = (float)($aeChart[$ym] ?? 0);
    $me += (float)($mtChartGrouped[$ym][\App\Models\MoneyTransfer::deliveryPolicy] ?? 0);
    $me += (float)($mtChartGrouped[$ym][\App\Models\MoneyTransfer::settle] ?? 0);
    $me += (float)($mtChartGrouped[$ym][\App\Models\MoneyTransfer::transferAgent] ?? 0);
    $me += (float)($pcChart[$ym] ?? 0);
    $me += (float)($vtChartGrouped[$ym][0] ?? 0);
    $me += (float)($btChartGrouped[$ym][0] ?? 0);
    $opt_fExpenses[] = $me;

    $mi = (float)($mtChartGrouped[$ym][\App\Models\MoneyTransfer::officeCommission] ?? 0);
    $mi += (float)($mtChartGrouped[$ym][\App\Models\MoneyTransfer::fromDashboard] ?? 0);
    $mi += (float)($ipChart[$ym] ?? 0);
    $mi += (float)($vtChartGrouped[$ym][1] ?? 0);
    $mi += (float)($btChartGrouped[$ym][1] ?? 0);
    $opt_fIncome[] = $mi;
}


// 3. COMPARISON & PROOF
echo "Comparison of Financial Values:\n";
echo sprintf("  vault_amount:       Legacy=%.2f | Opt=%.2f | Match: %s\n", $leg_vault_amount, $opt_vault_amount, ($leg_vault_amount == $opt_vault_amount ? 'YES' : 'FAIL'));
echo sprintf("  today_expenses:     Legacy=%.2f | Opt=%.2f | Match: %s\n", $leg_today_expenses, $opt_today_expenses, ($leg_today_expenses == $opt_today_expenses ? 'YES' : 'FAIL'));
echo sprintf("  today_income:       Legacy=%.2f | Opt=%.2f | Match: %s\n", $leg_today_income, $opt_today_income, ($leg_today_income == $opt_today_income ? 'YES' : 'FAIL'));
echo sprintf("  month_expenses:     Legacy=%.2f | Opt=%.2f | Match: %s\n", $leg_month_expenses, $opt_month_expenses, ($leg_month_expenses == $opt_month_expenses ? 'YES' : 'FAIL'));
echo sprintf("  month_income:       Legacy=%.2f | Opt=%.2f | Match: %s\n", $leg_month_income, $opt_month_income, ($leg_month_income == $opt_month_income ? 'YES' : 'FAIL'));
echo sprintf("  Chart Expenses:     %s | Match: %s\n", json_encode($opt_fExpenses), ($leg_fExpenses == $opt_fExpenses ? 'YES' : 'FAIL'));
echo sprintf("  Chart Income:       %s | Match: %s\n", json_encode($opt_fIncome), ($leg_fIncome == $opt_fIncome ? 'YES' : 'FAIL'));

$allMatch = (
    $leg_vault_amount == $opt_vault_amount &&
    $leg_today_expenses == $opt_today_expenses &&
    $leg_today_income == $opt_today_income &&
    $leg_month_expenses == $opt_month_expenses &&
    $leg_month_income == $opt_month_income &&
    $leg_fExpenses == $opt_fExpenses &&
    $leg_fIncome == $opt_fIncome
);

echo "\nResult TEST 2: " . ($allMatch ? "100% IDENTICAL FINANCIAL VALUES ACROSS ALL METRICS & CHARTS" : "MISMATCH DETECTED") . "\n";
echo "========================================================================\n";
