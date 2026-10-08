<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class DashbaordController extends Controller
{
    public function __invoke()
    {
        $hasVaults = Schema::hasTable('vaults');
        $hasVaultTx = Schema::hasTable('vault_transactions');

        $todayStart = now()->startOfDay()->toDateTimeString();
        $todayEnd = now()->endOfDay()->toDateTimeString();
        $weekStart = now()->startOfWeek()->toDateTimeString();
        $weekEnd = now()->endOfWeek()->toDateTimeString();
        $monthStart = now()->startOfMonth()->toDateTimeString();
        $monthEnd = now()->endOfMonth()->toDateTimeString();
        $sixMonthsAgo = now()->subMonths(5)->startOfMonth()->toDateTimeString();

        // 1. إحصائيات الحجوزات (استعلام تجميعي واحد بدلاً من 4)
        $bAgg = Booking::selectRaw("
            COUNT(*) as total_count,
            COUNT(CASE WHEN created_at BETWEEN ? AND ? THEN 1 END) as today_count,
            COUNT(CASE WHEN created_at BETWEEN ? AND ? THEN 1 END) as week_count,
            COUNT(CASE WHEN created_at BETWEEN ? AND ? THEN 1 END) as month_count
        ", [$todayStart, $todayEnd, $weekStart, $weekEnd, $monthStart, $monthEnd])->first();

        // 2. إحصائيات الحاويات (استعلام تجميعي واحد بدلاً من 2)
        $bcAgg = BookingContainer::selectRaw("
            COUNT(*) as total_count,
            COUNT(CASE WHEN created_at BETWEEN ? AND ? THEN 1 END) as today_count
        ", [$todayStart, $todayEnd])->first();

        // 3. إحصائيات المستخدمين والأسطول
        $total_agents = Agent::count();
        $total_superagents = Superagent::count();
        $total_companies = Company::count();
        $total_cars = Car::count();
        $total_drivers = Driver::count();
        $vault_amount = $hasVaults ? (Vault::first()->amount ?? 0) : 0;

        // 4. إحصائيات البوليصات (استعلام تجميعي واحد بدلاً من 2)
        $dpAgg = DeliveryPolicy::selectRaw("
            COUNT(*) as total_count,
            COUNT(CASE WHEN created_at BETWEEN ? AND ? THEN 1 END) as today_count
        ", [$todayStart, $todayEnd])->first();

        // 5. إحصائيات الفواتير (استعلام تجميعي واحد بدلاً من 3)
        $invAgg = Invoice::selectRaw("
            COUNT(*) as total_count,
            COUNT(CASE WHEN created_at BETWEEN ? AND ? THEN 1 END) as today_count,
            COUNT(CASE WHEN created_at BETWEEN ? AND ? THEN 1 END) as month_count
        ", [$todayStart, $todayEnd, $monthStart, $monthEnd])->first();

        // 6. شيكات مستحقة خلال الثلاثة أيام القادمة
        $checks_due_within_3_days = InvoicePayment::where('payment_type', 'check')
            ->whereNull('check_paid_at')
            ->whereNotNull('check_due_date')
            ->whereBetween('check_due_date', [now()->startOfDay()->toDateString(), now()->addDays(3)->endOfDay()->toDateString()])
            ->count();

        // 7. المصروفات والواردات اليومية والشهرية (استعلامات مجمعة بدلاً من 24 استعلام)
        $aeAgg = AgentExpense::where('created_at', '>=', $monthStart)
            ->selectRaw("
                COALESCE(SUM(CASE WHEN created_at BETWEEN ? AND ? THEN value END), 0) as today_val,
                COALESCE(SUM(value), 0) as month_val
            ", [$todayStart, $todayEnd])->first();

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

        $pcAgg = Payingcar::where('created_at', '>=', $monthStart)
            ->selectRaw("
                COALESCE(SUM(CASE WHEN created_at BETWEEN ? AND ? THEN value END), 0) as today_val,
                COALESCE(SUM(value), 0) as month_val
            ", [$todayStart, $todayEnd])->first();

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

        $stats = [
            'total_bookings' => (int) $bAgg->total_count,
            'today_bookings' => (int) $bAgg->today_count,
            'week_bookings' => (int) $bAgg->week_count,
            'month_bookings' => (int) $bAgg->month_count,

            'total_containers' => (int) $bcAgg->total_count,
            'today_containers' => (int) $bcAgg->today_count,

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

            'total_delivery_policies' => (int) $dpAgg->total_count,
            'today_delivery_policies' => (int) $dpAgg->today_count,

            'total_invoices' => (int) $invAgg->total_count,
            'today_invoices' => (int) $invAgg->today_count,
            'month_invoices' => (int) $invAgg->month_count,

            'checks_due_within_3_days' => $checks_due_within_3_days,
        ];

        // 8. بيانات الرسم البياني للحجوزات (استعلام واحد مجمع بدلاً من 6)
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
        $bookingsChart = ['labels' => $bMonths, 'data' => $bData];

        // 9. بيانات الرسم البياني المالي (5 استعلامات مجمعة بدلاً من 72)
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
        $financialChart = ['labels' => $fMonths, 'expenses' => $fExpenses, 'income' => $fIncome];

        return view('admin.index', compact('stats', 'bookingsChart', 'financialChart'));
    }
}