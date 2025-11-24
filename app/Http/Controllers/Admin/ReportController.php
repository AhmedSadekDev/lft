<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Agent\StoreRequest;
use App\Http\Requests\Admin\Agent\UpdateRequest;
use App\Models\Agent;
use App\Models\LogActivity;
use App\Models\AgentExpense;
use App\Models\Payingcar;
use App\Notifications\AssignAgentPasswordNotification;
use App\Notifications\AssignPasswordNotification;
use App\Services\PasswordResetAgentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\LogActivityExport;
use App\Models\MoneyTransfer;

class ReportController extends Controller
{

    public function __construct()
    {
        // تم إلغاء صفحة التقارير اليومية المنفصلة
        // يمكن استخدام صلاحيات أخرى لتقارير المصروفات العامة إذا لزم الأمر
    }

    public function agent_reports(Agent $agent)
    {
        $log_activities = LogActivity::orderBy('id', 'desc')->where("attacher_id", $agent->id)->where("attacher_type", Agent::class)->get();
        return view('admin.agents.reports.index', compact("log_activities"));
    }

    // public function daily_reports(Request $request)
    // {
    //     $query = LogActivity::query();
    //     if ($request->filled('from')) {
    //         $query->whereDate('created_at', '>=', $request->from);
    //     }
    //
    //     if ($request->filled('to')) {
    //         $query->whereDate('created_at', '<=', $request->to);
    //     }
    //
    //     $log_activities = $query->latest()->get();
    //     return view('admin.reports.index', compact("log_activities"));
    // }

    public function general_expenses(Request $request)
    {
        // جلب البيانات من جميع المصادر
        $agentExpenses = $this->getAgentExpenses($request);
        $deliveryPolicies = $this->getDeliveryPolicies($request);
        $officeCommissions = $this->getOfficeCommissions($request);
        $payingCars = $this->getPayingCars($request);
        $logActivities = $this->getLogActivities($request);

        // تصفية LogActivity لإزالة التكرار
        $filteredLogActivities = $this->filterDuplicateLogActivities(
            $logActivities,
            $agentExpenses,
            $deliveryPolicies,
            $officeCommissions,
            $payingCars
        );

        // دمج جميع البيانات
        $expenses = $agentExpenses
            ->concat($deliveryPolicies)
            ->concat($officeCommissions)
            ->concat($payingCars)
            ->concat($filteredLogActivities)
            ->sortByDesc('created_at');

        // تطبيق البحث
        if ($request->filled('search')) {
            $expenses = $this->applySearch($expenses, $request->search);
        }

        // حساب الإجماليات
        $totals = $this->calculateTotals($expenses);

        // تطبيق Pagination
        $paginatedExpenses = $this->paginateCollection($expenses, $request);

        return view('admin.reports.general_expenses', [
            'expenses' => $paginatedExpenses,
            'totalExpenses' => $totals['expenses'],
            'totalIncome' => $totals['income'],
            'search' => $request->search,
            'from' => $request->from,
            'to' => $request->to,
        ]);
    }

    /**
     * تطبيق فلترة التاريخ على Query
     */
    private function applyDateFilter($query, $from, $to)
    {
        return $query
            ->when($from, fn($q) => $q->where('created_at', '>=', $from))
            ->when($to, fn($q) => $q->where('created_at', '<=', $to));
    }

    /**
     * جلب مصروفات المندوبين
     */
    private function getAgentExpenses(Request $request)
    {
        $query = AgentExpense::with([
            'agent',
            'service.serviceCategory',
            'bookingContainer.booking',
            'delivery_policy'
        ]);

        return $this->applyDateFilter($query, $request->from, $request->to)
            ->latest()
            ->get();
    }

    /**
     * جلب معاملات عهدة السيارة
     */
    private function getDeliveryPolicies(Request $request)
    {
        $query = MoneyTransfer::with([
            'transferer',
            'transfered',
            'delivery_policy.booking_containers.booking',
            'delivery_policy.image'
        ])->where('type', MoneyTransfer::deliveryPolicy);

        return $this->applyDateFilter($query, $request->from, $request->to)
            ->latest()
            ->get();
    }

    /**
     * جلب معاملات دخان المكتب
     */
    private function getOfficeCommissions(Request $request)
    {
        $query = MoneyTransfer::with([
            'transfered',
            'delivery_policy.booking_containers.booking',
            'delivery_policy.image'
        ])->where('type', MoneyTransfer::officeCommission);

        return $this->applyDateFilter($query, $request->from, $request->to)
            ->latest()
            ->get();
    }

    /**
     * جلب سداد المديونية للسيارات (Payingcar)
     */
    private function getPayingCars(Request $request)
    {
        $query = Payingcar::with([
            'car',
            'user',
            'delivery_policy.booking_containers.booking',
            'delivery_policy.image'
        ]);

        return $this->applyDateFilter($query, $request->from, $request->to)
            ->latest()
            ->get();
    }

    /**
     * جلب سجلات النشاط المالية
     */
    private function getLogActivities(Request $request)
    {
        $query = LogActivity::with([
            'attacher',
            'log',
            'log.delivery_policy.booking_containers.booking',
            'log.delivery_policy.image'
        ])->whereIn('log_type', [
            AgentExpense::class,
            MoneyTransfer::class
        ]);

        $logActivities = $this->applyDateFilter($query, $request->from, $request->to)
            ->latest()
            ->get();

        // تحميل علاقة bookingContainer لسجلات AgentExpense
        foreach ($logActivities as $logActivity) {
            if ($logActivity->log_type === AgentExpense::class && $logActivity->log) {
                $logActivity->log->loadMissing('bookingContainer.booking');
            }
        }

        return $logActivities;
    }

    /**
     * تصفية LogActivity لإزالة السجلات المكررة
     */
    private function filterDuplicateLogActivities($logActivities, $agentExpenses, $deliveryPolicies, $officeCommissions, $payingCars = null)
    {
        // جمع IDs للسجلات المباشرة
        $existingIds = collect()
            ->merge($agentExpenses->map(fn($e) => ['type' => AgentExpense::class, 'id' => $e->id]))
            ->merge($deliveryPolicies->map(fn($t) => ['type' => MoneyTransfer::class, 'id' => $t->id]))
            ->merge($officeCommissions->map(fn($t) => ['type' => MoneyTransfer::class, 'id' => $t->id]));

        // إضافة Payingcar IDs إذا كانت موجودة
        if ($payingCars) {
            $existingIds = $existingIds->merge($payingCars->map(fn($p) => ['type' => Payingcar::class, 'id' => $p->id]));
        }

        // تصفية LogActivity
        return $logActivities->reject(function ($logActivity) use ($existingIds) {
            return $existingIds->contains(function ($existing) use ($logActivity) {
                return $existing['type'] === $logActivity->log_type &&
                       $existing['id'] == $logActivity->log_id;
            });
        });
    }

    /**
     * تطبيق البحث على البيانات
     */
    private function applySearch($expenses, $search)
    {
        $searchLower = mb_strtolower($search, 'UTF-8');

        return $expenses->filter(function ($item) use ($searchLower) {
            $data = $this->extractSearchableData($item);

            return mb_strpos(mb_strtolower($data['agentName'], 'UTF-8'), $searchLower) !== false ||
                   mb_strpos(mb_strtolower($data['bookingNumber'], 'UTF-8'), $searchLower) !== false ||
                   mb_strpos(mb_strtolower($data['service'], 'UTF-8'), $searchLower) !== false;
        });
    }

    /**
     * استخراج البيانات القابلة للبحث من السجل
     */
    private function extractSearchableData($item)
    {
        $data = ['agentName' => '', 'bookingNumber' => '', 'service' => ''];

        if ($item instanceof LogActivity) {
            $data['agentName'] = $item->attacher?->name ?? '';
            $log = $item->log;
            if ($log) {
                if ($item->log_type === AgentExpense::class && $log->bookingContainer) {
                    $data['bookingNumber'] = $log->bookingContainer->booking?->booking_number ?? '';
                } elseif ($item->log_type === MoneyTransfer::class && $log->delivery_policy) {
                    $firstContainer = $log->delivery_policy->booking_containers->first();
                    $data['bookingNumber'] = $firstContainer->booking?->booking_number ?? '';
                }
            }
        } elseif ($item instanceof MoneyTransfer) {
            $data['agentName'] = trim(($item->transferer?->name ?? '') . ' ' . ($item->transfered?->name ?? ''));
            if ($item->delivery_policy) {
                $firstContainer = $item->delivery_policy->booking_containers->first();
                $data['bookingNumber'] = $firstContainer->booking?->booking_number ?? '';
            }
        } elseif ($item instanceof AgentExpense) {
            $data['agentName'] = $item->agent?->name ?? '';
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
        }

        return $data;
    }

    /**
     * حساب الإجماليات
     */
    private function calculateTotals($expenses)
    {
        $totals = ['expenses' => 0, 'income' => 0];

        foreach ($expenses as $item) {
            $values = $this->extractValues($item);
            $totals['expenses'] += $values['expense'];
            $totals['income'] += $values['income'];
        }

        return $totals;
    }

    /**
     * استخراج القيم من السجل
     */
    private function extractValues($item)
    {
        $values = ['expense' => 0, 'income' => 0];

        if ($item instanceof LogActivity) {
            $log = $item->log;
            $value = $log?->value ?? 0;

            if ($item->log_type === AgentExpense::class) {
                $values['expense'] = $value;
            } elseif ($item->log_type === MoneyTransfer::class && $log) {
                $type = $log->type;
                if (in_array($type, [
                    MoneyTransfer::deliveryPolicy,
                    MoneyTransfer::transferAgent,
                    MoneyTransfer::settle
                ])) {
                    $values['expense'] = $value;
                } elseif (in_array($type, [
                    MoneyTransfer::officeCommission,
                    MoneyTransfer::fromDashboard
                ])) {
                    $values['income'] = $value;
                }
            }
        } elseif ($item instanceof MoneyTransfer) {
            $value = $item->value ?? 0;
            if ($item->type == 3) {
                $values['expense'] = $value;
            } elseif ($item->type == 5) {
                $values['income'] = $value;
            }
        } elseif ($item instanceof AgentExpense) {
            $values['expense'] = $item->value ?? 0;
        } elseif ($item instanceof Payingcar) {
            // سداد المديونية للسيارات (منصرف)
            $values['expense'] = (float) ($item->value ?? 0);
        }

        return $values;
    }

    /**
     * تطبيق Pagination على Collection
     */
    private function paginateCollection($collection, Request $request)
    {
        $perPage = $request->get('per_page', 15);
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $currentItems = $collection->slice(($currentPage - 1) * $perPage, $perPage)->values();

        return new LengthAwarePaginator(
            $currentItems,
            $collection->count(),
            $perPage,
            $currentPage,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );
    }
    public function exportExcel(Request $request)
    {
        return Excel::download(new LogActivityExport($request), 'التقارير_اليومية.xlsx');
    }
}
