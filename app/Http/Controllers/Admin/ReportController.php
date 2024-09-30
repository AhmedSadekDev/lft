<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Agent\StoreRequest;
use App\Http\Requests\Admin\Agent\UpdateRequest;
use App\Models\Agent;
use App\Models\LogActivity;
use App\Notifications\AssignAgentPasswordNotification;
use App\Notifications\AssignPasswordNotification;
use App\Services\PasswordResetAgentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

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

    public function daily_reports()
    {
        $log_activities = LogActivity::orderBy('id', 'desc')->whereDate("created_at", now())->get();
        return view('admin.reports.index', compact("log_activities"));
    }
}
