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
use Carbon\Carbon;

class DashbaordController extends Controller
{
    public function __invoke()
    {
        // إحصائيات عامة
        $stats = [
            // إحصائيات الحجوزات
            'total_bookings' => Booking::count(),
            'today_bookings' => Booking::whereDate('created_at', today())->count(),
            'week_bookings' => Booking::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'month_bookings' => Booking::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count(),

            // إحصائيات الحاويات
            'total_containers' => BookingContainer::count(),
            'today_containers' => BookingContainer::whereDate('created_at', today())->count(),

            // إحصائيات المستخدمين
            'total_agents' => Agent::count(),
            'total_superagents' => Superagent::count(),
            'total_companies' => Company::count(),

            // إحصائيات السيارات والسائقين
            'total_cars' => Car::count(),
            'total_drivers' => Driver::count(),

            // إحصائيات مالية
            'vault_amount' => Vault::first()->amount ?? 0,
            'today_expenses' => $this->getTodayExpenses(),
            'today_income' => $this->getTodayIncome(),
            'month_expenses' => $this->getMonthExpenses(),
            'month_income' => $this->getMonthIncome(),

            // إحصائيات البوليصات
            'total_delivery_policies' => DeliveryPolicy::count(),
            'today_delivery_policies' => DeliveryPolicy::whereDate('created_at', today())->count(),

            // إحصائيات الفواتير
            'total_invoices' => Invoice::count(),
            'today_invoices' => Invoice::whereDate('created_at', today())->count(),
            'month_invoices' => Invoice::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count(),
        ];

        // بيانات الرسوم البيانية - الحجوزات حسب الشهر (آخر 6 أشهر)
        $bookingsChart = $this->getBookingsChartData();

        // بيانات الرسوم البيانية - المصروفات والواردات (آخر 6 أشهر)
        $financialChart = $this->getFinancialChartData();

        return view('admin.index', compact('stats', 'bookingsChart', 'financialChart'));
    }

    private function getTodayExpenses()
    {
        $agentExpenses = AgentExpense::whereDate('created_at', today())->sum('value');
        $deliveryPolicies = MoneyTransfer::where('type', MoneyTransfer::deliveryPolicy)
            ->whereDate('created_at', today())
            ->sum('value');
        $settle = MoneyTransfer::where('type', MoneyTransfer::settle)
            ->whereDate('created_at', today())
            ->sum('value');
        $transferAgent = MoneyTransfer::where('type', MoneyTransfer::transferAgent)
            ->whereDate('created_at', today())
            ->sum('value');
        $payingCars = Payingcar::whereDate('created_at', today())->sum('value');
        $vaultTransactions = VaultTransaction::where('type', 0)
            ->whereDate('created_at', today())
            ->sum('amount');
        $bankTransactions = BankTrnsaction::where('type', 0)
            ->whereDate('created_at', today())
            ->sum('amount');

        return $agentExpenses + $deliveryPolicies + $settle + $transferAgent + $payingCars + $vaultTransactions + $bankTransactions;
    }

    private function getTodayIncome()
    {
        $officeCommissions = MoneyTransfer::where('type', MoneyTransfer::officeCommission)
            ->whereDate('created_at', today())
            ->sum('value');
        $fromDashboard = MoneyTransfer::where('type', MoneyTransfer::fromDashboard)
            ->whereDate('created_at', today())
            ->sum('value');
        $invoicePayments = InvoicePayment::where(function($query) {
                $query->where('payment_type', '!=', 'check')
                      ->orWhere(function($q) {
                          $q->where('payment_type', 'check')
                            ->whereNotNull('check_paid_at');
                      });
            })
            ->whereDate('created_at', today())
            ->sum('value');
        $vaultTransactions = VaultTransaction::where('type', 1)
            ->whereDate('created_at', today())
            ->sum('amount');
        $bankTransactions = BankTrnsaction::where('type', 1)
            ->whereDate('created_at', today())
            ->sum('amount');

        return $officeCommissions + $fromDashboard + $invoicePayments + $vaultTransactions + $bankTransactions;
    }

    private function getMonthExpenses()
    {
        $agentExpenses = AgentExpense::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('value');
        $deliveryPolicies = MoneyTransfer::where('type', MoneyTransfer::deliveryPolicy)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('value');
        $settle = MoneyTransfer::where('type', MoneyTransfer::settle)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('value');
        $transferAgent = MoneyTransfer::where('type', MoneyTransfer::transferAgent)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('value');
        $payingCars = Payingcar::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('value');
        $vaultTransactions = VaultTransaction::where('type', 0)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');
        $bankTransactions = BankTrnsaction::where('type', 0)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');

        return $agentExpenses + $deliveryPolicies + $settle + $transferAgent + $payingCars + $vaultTransactions + $bankTransactions;
    }

    private function getMonthIncome()
    {
        $officeCommissions = MoneyTransfer::where('type', MoneyTransfer::officeCommission)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('value');
        $fromDashboard = MoneyTransfer::where('type', MoneyTransfer::fromDashboard)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('value');
        $invoicePayments = InvoicePayment::where(function($query) {
                $query->where('payment_type', '!=', 'check')
                      ->orWhere(function($q) {
                          $q->where('payment_type', 'check')
                            ->whereNotNull('check_paid_at');
                      });
            })
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('value');
        $vaultTransactions = VaultTransaction::where('type', 1)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');
        $bankTransactions = BankTrnsaction::where('type', 1)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');

        return $officeCommissions + $fromDashboard + $invoicePayments + $vaultTransactions + $bankTransactions;
    }

    private function getBookingsChartData()
    {
        $months = [];
        $data = [];

        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $months[] = $date->format('M Y');
            $data[] = Booking::whereMonth('created_at', $date->month)
                ->whereYear('created_at', $date->year)
                ->count();
        }

        return [
            'labels' => $months,
            'data' => $data
        ];
    }

    private function getFinancialChartData()
    {
        $months = [];
        $expenses = [];
        $income = [];

        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $months[] = $date->format('M Y');

            // المصروفات
            $monthExpenses = AgentExpense::whereMonth('created_at', $date->month)
                ->whereYear('created_at', $date->year)
                ->sum('value');
            $monthExpenses += MoneyTransfer::where('type', MoneyTransfer::deliveryPolicy)
                ->whereMonth('created_at', $date->month)
                ->whereYear('created_at', $date->year)
                ->sum('value');
            $monthExpenses += MoneyTransfer::where('type', MoneyTransfer::settle)
                ->whereMonth('created_at', $date->month)
                ->whereYear('created_at', $date->year)
                ->sum('value');
            $monthExpenses += MoneyTransfer::where('type', MoneyTransfer::transferAgent)
                ->whereMonth('created_at', $date->month)
                ->whereYear('created_at', $date->year)
                ->sum('value');
            $monthExpenses += Payingcar::whereMonth('created_at', $date->month)
                ->whereYear('created_at', $date->year)
                ->sum('value');
            $monthExpenses += VaultTransaction::where('type', 0)
                ->whereMonth('created_at', $date->month)
                ->whereYear('created_at', $date->year)
                ->sum('amount');
            $monthExpenses += BankTrnsaction::where('type', 0)
                ->whereMonth('created_at', $date->month)
                ->whereYear('created_at', $date->year)
                ->sum('amount');
            $expenses[] = $monthExpenses;

            // الواردات
            $monthIncome = MoneyTransfer::where('type', MoneyTransfer::officeCommission)
                ->whereMonth('created_at', $date->month)
                ->whereYear('created_at', $date->year)
                ->sum('value');
            $monthIncome += MoneyTransfer::where('type', MoneyTransfer::fromDashboard)
                ->whereMonth('created_at', $date->month)
                ->whereYear('created_at', $date->year)
                ->sum('value');
            $monthIncome += InvoicePayment::where(function($query) {
                    $query->where('payment_type', '!=', 'check')
                          ->orWhere(function($q) {
                              $q->where('payment_type', 'check')
                                ->whereNotNull('check_paid_at');
                          });
                })
                ->whereMonth('created_at', $date->month)
                ->whereYear('created_at', $date->year)
                ->sum('value');
            $monthIncome += VaultTransaction::where('type', 1)
                ->whereMonth('created_at', $date->month)
                ->whereYear('created_at', $date->year)
                ->sum('amount');
            $monthIncome += BankTrnsaction::where('type', 1)
                ->whereMonth('created_at', $date->month)
                ->whereYear('created_at', $date->year)
                ->sum('amount');
            $income[] = $monthIncome;
        }

        return [
            'labels' => $months,
            'expenses' => $expenses,
            'income' => $income
        ];
    }
}
