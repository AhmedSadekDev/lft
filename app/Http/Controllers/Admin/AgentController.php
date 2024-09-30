<?php

namespace App\Http\Controllers\Admin;

use App\Models\Agent;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Controllers\Controller;
use App\Services\PasswordResetAgentService;
use Illuminate\Support\Facades\Notification;
use App\Http\Requests\Admin\Agent\StoreRequest;
use App\Http\Requests\Admin\Agent\UpdateRequest;
use App\Notifications\AssignPasswordNotification;
use App\Notifications\AssignAgentPasswordNotification;

class AgentController extends Controller
{
    protected $passwordResetAgentService;

    public function __construct(PasswordResetAgentService $passwordResetAgentService)
    {
        $this->passwordResetAgentService = $passwordResetAgentService;

        $this->middleware('permission:agents.index')->only('index');
        $this->middleware('permission:agents.create')->only(['create', 'store']);
        $this->middleware('permission:agents.udpate')->only(['edit', 'udpate']);
        $this->middleware('permission:agents.delete')->only('destroy');
    }
    public function index()
    {
        $input = [
            'agents' => Agent::all(),
        ];
        return view('admin.agents.index', $input);
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $input = [
            'method'    => 'POST',
            'action'    => route('agents.store')
        ];

        return view('admin.agents.create', $input);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreRequest $request)
    {
        $agent = Agent::create($request->validated());
        $token = JWTAuth::fromUser($agent);
        $agent->update(array_merge(['session_id' => $token]));
         Notification::send($agent, new AssignAgentPasswordNotification($agent));
        $otp = $this->passwordResetAgentService->sendOtp($request->email);

        return redirect()->route('agents.index')
            ->with('success', __('alerts.added_successfully'));
    }


    public function edit(Agent $agent)
    {
        $input = [
            'method'    => 'PUT',
            'action'    => route('agents.update', $agent->id),
            'agent'   => $agent,
        ];

        return view('admin.agents.edit', $input);
    }


    public function update(UpdateRequest $request, Agent $agent)
    {
        $agent->update($request->validated());
        return redirect()->route('agents.index')
            ->with('success', __('alerts.updated_successfully'));
    }


    public function destroy(Agent $agent)
    {
        $agent->delete();
        return response()->json(['staus' => true, 'msg' => __('alerts.deleted_successfully')], 200);
    }

}
