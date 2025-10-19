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

class ReportController extends Controller
{

    public function __construct()
    {
        $this->middleware('permission:daily_reports.index')->only('daily_reports');
    }

    public function agent_reports(Agent $agent)
    {
        $log_activities = LogActivity::orderBy('id', 'desc')->where("attacher_id", $agent->id)->where("attacher_type", Agent::class)->get();
        return view('admin.agents.reports.index', compact("log_activities"));
    }

    public function daily_reports(Request $request)
    {
        $query = LogActivity::query();
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }
    
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }
    
        $log_activities = $query->latest()->get();
        return view('admin.reports.index', compact("log_activities"));
    }
    
    public function general_expenses(Request $request)
    {
        $expenses = AgentExpense::query();
        if($request->from)
        {
            $expenses->where('created_at', '>=', $request->from);
        }
        if($request->to)
        {
            $expenses->where('created_at', '<=', $request->to);
        }
        $expenses = $expenses->whereHas('bookingContainer')->latest()->get();
        return view('admin.reports.general_expenses', compact("expenses"));
    }
    public function exportExcel(Request $request)
    {
        return Excel::download(new LogActivityExport($request), 'التقارير_اليومية.xlsx');
    }
}
