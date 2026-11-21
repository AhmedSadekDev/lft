<?php

namespace App\Http\Controllers\Admin;

use App\Exports\AgentAccountExport;
use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\AgentExpense;
use App\Models\MoneyTransfer;
use App\Services\AgentCustodyStatementService;
use App\Services\AgentVaultReturnService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class ExpenseController extends Controller
{
    public function agent_expenses(Request $request, Agent $agent)
    {
        $from = $request->input('from');
        $to = $request->input('to');

        $statement = app(AgentCustodyStatementService::class)->build($agent, $from, $to);
        $allExpenses = $statement['rows']->map(fn (array $row) => $row['model'])->values();
        $balancesByKey = $statement['balances_by_key'];
        $openingBalance = $statement['opening_balance'];
        $closingBalance = $statement['closing_balance'];

        $expenseModels = $allExpenses->filter(fn ($item) => $item instanceof AgentExpense)->values();
        if ($expenseModels->isNotEmpty()) {
            $expenseModels->loadMissing(['bookingService.agent', 'bookingService.creator', 'booking']);
        }

        $shownExpenseIds = $expenseModels->pluck('id');
        $awaitingReviewIds = $agent->expenses()
            ->awaitingDashboardReview()
            ->whereIn('id', $shownExpenseIds)
            ->pluck('id')
            ->flip();

        $pendingVaultReturnIds = $allExpenses
            ->filter(fn ($item) => $item instanceof MoneyTransfer
                && (int) $item->type === MoneyTransfer::vaultReturnPending)
            ->pluck('id')
            ->flip();

        return view('admin.agents.expenses.index', compact(
            'allExpenses',
            'agent',
            'awaitingReviewIds',
            'pendingVaultReturnIds',
            'balancesByKey',
            'openingBalance',
            'closingBalance',
            'from',
            'to'
        ));
    }

    public function export_agent_expenses(Request $request, Agent $agent)
    {
        $filename = 'agent-account-' . $agent->id . '-' . now()->format('Y-m-d') . '.xlsx';

        return Excel::download(
            new AgentAccountExport($agent, $request->input('from'), $request->input('to')),
            $filename
        );
    }

    public function review_agent_expenses(Agent $agent, Request $request)
    {
        $ids = $request->input('ids');
        if (is_string($ids)) {
            $ids = array_filter(array_map('intval', explode(',', $ids)));
        } elseif (is_array($ids)) {
            $ids = array_values(array_filter(array_map('intval', $ids)));
        } else {
            $ids = null; // مراجعة الكل
        }

        if ($ids !== null && $ids === []) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'اختر إيصالاً واحداً على الأقل'], 422);
            }

            return back()->with('error', 'اختر إيصالاً واحداً على الأقل');
        }

        try {
            $count = app(\App\Services\StageExpenseService::class)->markReviewed(
                $agent->id,
                $ids,
                auth()->id()
            );
            $message = $count
                ? "تمت مراجعة {$count} إيصال وخصم العهدة"
                : 'لا توجد إيصالات بانتظار المراجعة';

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'count' => $count,
                    'ids' => $ids ?? [],
                ], 200);
            }

            return back()->with('success', $message);
        } catch (\Exception $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], $e instanceof HttpExceptionInterface ? $e->getStatusCode() : 500);
            }

            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * موافقة على إيصال واحد من صفحة مصروفات المندوب.
     */
    public function approve($id)
    {
        try {
            $expense = AgentExpense::findOrFail($id);
            $count = app(\App\Services\StageExpenseService::class)->markReviewed(
                (int) $expense->agent_id,
                [$expense->id],
                auth()->id()
            );

            if (request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $count ? 'تمت الموافقة على الإيصال' : 'الإيصال غير بانتظار المراجعة',
                ], 200);
            }

            return back()->with('success', $count ? 'تمت الموافقة على الإيصال' : 'الإيصال غير بانتظار المراجعة');
        } catch (\Exception $Exception) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            if (request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $Exception->getMessage(),
                ], $Exception instanceof HttpExceptionInterface
                    ? $Exception->getStatusCode()
                    : 500);
            }

            return back()->with('error', $Exception->getMessage());
        }
    }

    public function approve_vault_return($id)
    {
        try {
            app(AgentVaultReturnService::class)->approve((int) $id);

            if (request()->ajax() || request()->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'تمت الموافقة على التحويل للخزنة'], 200);
            }

            return back()->with('success', 'تمت الموافقة على التحويل للخزنة');
        } catch (\Exception $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            if (request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], $e instanceof HttpExceptionInterface ? $e->getStatusCode() : 500);
            }

            return back()->with('error', $e->getMessage());
        }
    }

    public function reject_vault_return($id)
    {
        try {
            app(AgentVaultReturnService::class)->reject((int) $id);

            if (request()->ajax() || request()->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'تم رفض التحويل وإعادة المبلغ للمندوب'], 200);
            }

            return back()->with('success', 'تم رفض التحويل وإعادة المبلغ للمندوب');
        } catch (\Exception $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            if (request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], $e instanceof HttpExceptionInterface ? $e->getStatusCode() : 500);
            }

            return back()->with('error', $e->getMessage());
        }
    }

    public function booking_container_expenses($id)
    {
        $allExpenses = AgentExpense::with(['agent', 'bookingService.agent', 'bookingService.creator'])
            ->where('booking_container_id', $id)->get();

        return view('admin.bookings.booking-containers.expenses', compact('allExpenses'));
    }

    /**
     * إرجاع إيصال معتمد إلى بانتظار المراجعة دون حذفه.
     */
    public function unreview($id)
    {
        try {
            request()->validate(['version' => 'required|integer|min:1']);
            $expense = AgentExpense::findOrFail($id);
            app(\App\Services\StageExpenseService::class)->unreview(
                (int) $expense->agent_id,
                $expense->id,
                (int) request('version')
            );

            if (request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'تم إرجاع الإيصال إلى بانتظار المراجعة',
                ], 200);
            }

            return back()->with('success', 'تم إرجاع الإيصال إلى بانتظار المراجعة');
        } catch (\Exception $Exception) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            if (request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $Exception->getMessage(),
                ], $Exception instanceof HttpExceptionInterface
                    ? $Exception->getStatusCode()
                    : 500);
            }

            return back()->with('error', $Exception->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            request()->validate(['version' => 'required|integer|min:1']);
            $expense = AgentExpense::findOrFail($id);
            app(\App\Services\StageExpenseService::class)->change((int) $expense->agent_id, $expense->id, (int) request('version'), null, null, true);

            if (request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => __('alerts.deleted_successfully'),
                ], 200);
            }

            return back()->with('success', __('alerts.deleted_successfully'));
        } catch (\Exception $Exception) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            if (request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $Exception->getMessage(),
                ], 500);
            }

            return back()->with('error', $Exception->getMessage());
        }
    }
}
