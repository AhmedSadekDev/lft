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
use Illuminate\Support\Facades\DB;

class ExpenseController extends Controller
{

    public function agent_expenses(Agent $agent)
    {
        $financial_custodies = collect();
        $expenses = collect();

        $financial_custodies = $agent->sended_financial_custodies()
            ->orderBy("id", "desc")
            ->get();
        // dd($financial_custodies);
        $expenses = $agent->expenses()
            ->orderBy("id", "desc")
            ->get();
        // dd($expenses);
        $merged = $financial_custodies->concat($expenses);

        $ordered = $merged->sortBy('created_at')->values();
        $allExpenses = $ordered;
        // dd($allExpenses);
        return view('admin.agents.expenses.index', compact("allExpenses"));
    }


    public function booking_container_expenses($id)
    {
        $allExpenses = AgentExpense::where('booking_container_id', $id)->get();
        return view('admin.bookings.booking-containers.expenses', compact("allExpenses"));
    }

    public function destroy($id)
    {
        try {
            $expense = AgentExpense::with(['delivery_policy.money_transfer', 'agent'])->findOrFail($id);

            DB::beginTransaction();

            // إذا كان المصروف مرتبط بعهدة، إرجاع القيمة للعهدة
            if ($expense->delivery_policy_id && $expense->delivery_policy) {
                $deliveryPolicy = $expense->delivery_policy;

                // التحقق من أن العهدة لم يتم تسويتها
                if ($deliveryPolicy->is_settled == 1) {
                    DB::rollBack();
                    return back()->with('error', __('main.delivery_policy is settled'));
                }

                // إرجاع القيمة للعهدة عن طريق زيادة قيمة money_transfer
                if ($deliveryPolicy->money_transfer) {
                    $moneyTransfer = $deliveryPolicy->money_transfer;
                    $moneyTransfer->update([
                        'value' => $moneyTransfer->value + $expense->value
                    ]);
                }
            } else {
                // إذا لم يكن مرتبط بعهدة، إرجاع القيمة للمحفظة
                if ($expense->agent) {
                    $expense->agent->update(['wallet' => $expense->agent->wallet + $expense->value]);
                }
            }

            // delete image file if exists
            if ($expense->image_agent_expenses) {
                $path = public_path('Admin/images/expenses/' . $expense->image_agent_expenses);
                if (file_exists($path)) {
                    @unlink($path);
                }
            }

            $expense->delete();

            DB::commit();

            return back()->with('success', __('alerts.deleted_successfully'));
        } catch (\Exception $Exception) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            return back()->with('error', $Exception->getMessage());
        }
    }

}
