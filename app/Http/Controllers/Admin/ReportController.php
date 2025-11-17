<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Agent\StoreRequest;
use App\Http\Requests\Admin\Agent\UpdateRequest;
use App\Models\Agent;
use App\Models\LogActivity;
use App\Models\AgentExpense;
use App\Notifications\AssignAgentPasswordNotification;
use App\Notifications\AssignPasswordNotification;
use App\Services\PasswordResetAgentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
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
        // جلب المصروفات (AgentExpense)
        $expensesQuery = AgentExpense::with(['agent', 'service.serviceCategory', 'bookingContainer.booking', 'delivery_policy']);

        if($request->from)
        {
            $expensesQuery->where('created_at', '>=', $request->from);
        }
        if($request->to)
        {
            $expensesQuery->where('created_at', '<=', $request->to);
        }

        // إحضار جميع مصروفات المندوبين (سواء مرتبطة بحاوية أو لا)
        $agentExpenses = $expensesQuery->latest()->get();

        // جلب معاملات عهدة السيارة (type 3)
        $deliveryPoliciesQuery = MoneyTransfer::with(['transferer', 'transfered', 'delivery_policy.booking_containers.booking', 'delivery_policy.image'])
            ->where('type', MoneyTransfer::deliveryPolicy);

        if($request->from)
        {
            $deliveryPoliciesQuery->where('created_at', '>=', $request->from);
        }
        if($request->to)
        {
            $deliveryPoliciesQuery->where('created_at', '<=', $request->to);
        }

        $deliveryPolicies = $deliveryPoliciesQuery->latest()->get();

        // جلب معاملات دخان المكتب (type 5)
        $officeCommissionsQuery = MoneyTransfer::with(['transfered', 'delivery_policy.booking_containers.booking', 'delivery_policy.image'])
            ->where('type', MoneyTransfer::officeCommission);

        if($request->from)
        {
            $officeCommissionsQuery->where('created_at', '>=', $request->from);
        }
        if($request->to)
        {
            $officeCommissionsQuery->where('created_at', '<=', $request->to);
        }

        $officeCommissions = $officeCommissionsQuery->latest()->get();

        // جلب سجلات النشاط (LogActivity) - فقط السجلات المالية
        $logActivitiesQuery = LogActivity::with([
            'attacher',
            'log',
            'log.delivery_policy.booking_containers.booking',
            'log.delivery_policy.image'
        ])
            ->whereIn('log_type', [
                'App\Models\AgentExpense',
                'App\Models\MoneyTransfer'
            ]);

        if($request->from)
        {
            $logActivitiesQuery->where('created_at', '>=', $request->from);
        }
        if($request->to)
        {
            $logActivitiesQuery->where('created_at', '<=', $request->to);
        }

        $logActivities = $logActivitiesQuery->latest()->get();

        // تحميل علاقة bookingContainer لسجلات AgentExpense فقط
        foreach ($logActivities as $logActivity) {
            if ($logActivity->log_type === 'App\Models\AgentExpense' && $logActivity->log) {
                $logActivity->log->loadMissing('bookingContainer.booking');
            }
        }

        // جمع IDs للسجلات التي تم جلبها مباشرة لتجنب التكرار
        $existingIds = collect();

        // جمع IDs من AgentExpense
        $existingIds = $existingIds->merge(
            $agentExpenses->map(fn($expense) => [
                'type' => 'App\Models\AgentExpense',
                'id' => $expense->id
            ])
        );

        // جمع IDs من MoneyTransfer (deliveryPolicy و officeCommission)
        $existingIds = $existingIds->merge(
            $deliveryPolicies->map(fn($transfer) => [
                'type' => 'App\Models\MoneyTransfer',
                'id' => $transfer->id
            ])
        );

        $existingIds = $existingIds->merge(
            $officeCommissions->map(fn($transfer) => [
                'type' => 'App\Models\MoneyTransfer',
                'id' => $transfer->id
            ])
        );

        // تصفية LogActivity لاستبعاد السجلات المكررة
        $filteredLogActivities = $logActivities->filter(function ($logActivity) use ($existingIds) {
            // إذا كان log_id موجود في السجلات التي تم جلبها مباشرة، استبعده
            return !$existingIds->contains(function ($existing) use ($logActivity) {
                return $existing['type'] === $logActivity->log_type &&
                       $existing['id'] == $logActivity->log_id;
            });
        });

        // دمج المصروفات، عهدة السيارة، دخان المكتب، وسجلات النشاط المفلترة في مصفوفة واحدة
        $expenses = $agentExpenses
            ->concat($deliveryPolicies)
            ->concat($officeCommissions)
            ->concat($filteredLogActivities)
            ->sortByDesc('created_at');

        return view('admin.reports.general_expenses', compact("expenses"));
    }
    public function exportExcel(Request $request)
    {
        return Excel::download(new LogActivityExport($request), 'التقارير_اليومية.xlsx');
    }
}
