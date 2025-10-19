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



}
