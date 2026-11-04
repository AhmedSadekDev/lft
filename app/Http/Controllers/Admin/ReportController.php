<?php

namespace App\Http\Controllers\Admin;

use App\Exports\GeneralExpensesExport;
use App\Exports\LogActivityExport;
use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\AgentExpense;
use App\Models\Bank;
use App\Models\LogActivity;
use App\Services\DashboardExpenseService;
use App\Services\GeneralExpensesReportService;
use App\Services\StageExpenseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public function __construct()
    {
        // تم إلغاء صفحة التقارير اليومية المنفصلة
        // يمكن استخدام صلاحيات أخرى لتقارير المصروفات العامة إذا لزم الأمر
    }

    public function agent_reports(Agent $agent)
    {
        $log_activities = LogActivity::orderBy('id', 'desc')
            ->where('attacher_id', $agent->id)
            ->where('attacher_type', Agent::class)
            ->get();

        return view('admin.agents.reports.index', compact('log_activities'));
    }

    public function general_expenses(Request $request, GeneralExpensesReportService $report)
    {
        $built = $report->build($request);
        $paginatedExpenses = $report->paginate($built['expenses'], $request);
        $paginatedExpenses->appends(array_filter([
            'from' => $built['from'],
            'to' => $built['to'],
            'search' => $request->input('search'),
            'kind' => $request->input('kind'),
            'per_page' => $request->input('per_page'),
        ], fn ($value) => $value !== null && $value !== ''));

        return view('admin.reports.general_expenses', [
            'expenses' => $paginatedExpenses,
            'totalExpenses' => $built['totals']['expenses'],
            'totalIncome' => $built['totals']['income'],
            'search' => $request->search,
            'kind' => $built['kind'],
            'from' => $built['from'],
            'to' => $built['to'],
            'isDefaultRange' => $built['is_default_range'],
            'perPage' => $request->get('per_page', 15),
            'awaitingReviewIds' => $built['awaitingReviewIds'],
            'banks' => Bank::query()->orderBy('name')->get(['id', 'name', 'amount']),
        ]);
    }

    /**
     * تسجيل مصروف داشبورد (خصم من الخزنة أو البنك) من تقرير المصروفات العامة.
     */
    public function general_expenses_store(Request $request, DashboardExpenseService $service)
    {
        $data = $request->validate([
            'value' => 'required|numeric|gt:0',
            'reason' => 'required|string|max:255',
            'source' => 'required|in:vault,bank',
            'bank_id' => 'required_if:source,bank|nullable|exists:banks,id',
        ], [
            'value.required' => 'قيمة المصروف مطلوبة',
            'value.gt' => 'قيمة المصروف يجب أن تكون أكبر من صفر',
            'reason.required' => 'سبب المصروف مطلوب',
            'source.required' => 'اختر مكان الخصم',
            'bank_id.required_if' => 'اختر البنك',
        ]);

        try {
            $service->store($data, auth()->id());

            return back()->with('success', 'تم تسجيل المصروف وخصمه بنجاح');
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * اعتماد إيصالات المندوب من تقرير المصروفات العامة (خصم العهدة النهائي).
     */
    public function general_expenses_review(Request $request)
    {
        $data = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:agent_expenses,id',
        ]);

        try {
            $total = 0;
            DB::transaction(function () use ($data, &$total) {
                $grouped = AgentExpense::whereIn('id', $data['ids'])
                    ->get(['id', 'agent_id'])
                    ->groupBy('agent_id');
                $service = app(StageExpenseService::class);
                foreach ($grouped as $agentId => $rows) {
                    $total += $service->markReviewed((int) $agentId, $rows->pluck('id')->all(), auth()->id());
                }
            });

            return back()->with('success', $total
                ? "تمت مراجعة {$total} إيصال وخصم العهدة"
                : 'لا توجد إيصالات قابلة للمراجعة ضمن التحديد');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function exportExcel(Request $request)
    {
        return Excel::download(new LogActivityExport($request), 'التقارير_اليومية.xlsx');
    }

    /**
     * تصدير المصروفات العامة إلى Excel
     */
    public function general_expenses_export(Request $request)
    {
        $fileName = 'المصروفات_العامة_' . date('Y-m-d') . '.xlsx';

        return Excel::download(new GeneralExpensesExport($request), $fileName);
    }
}
