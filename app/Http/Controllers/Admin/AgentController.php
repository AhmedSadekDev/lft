<?php

namespace App\Http\Controllers\Admin;

use App\Models\Agent;
use App\Models\AgentExpense;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Controllers\Controller;
use App\Services\AgentEmailService;
use App\Http\Requests\Admin\Agent\StoreRequest;
use App\Http\Requests\Admin\Agent\UpdateRequest;
use Illuminate\Support\Facades\Log;

class AgentController extends Controller
{
    protected $agentEmailService;

    public function __construct(AgentEmailService $agentEmailService)
    {
        $this->agentEmailService = $agentEmailService;

        $this->middleware('permission:agents.index')->only('index');
        $this->middleware('permission:agents.create')->only(['create', 'store']);
        $this->middleware('permission:agents.udpate')->only(['edit', 'udpate']);
        $this->middleware('permission:agents.delete')->only('destroy');
    }
    public function index()
    {
        // نفس منطق تطبيق المندوب: تحت المراجعة = إيصالات لم تُخصم بعد من المحفظة
        $underReviewByAgent = AgentExpense::query()
            ->selectRaw('agent_id, COALESCE(SUM(value), 0) as under_review')
            ->whereNull('wallet_debited_at')
            ->whereNull('delivery_policy_id')
            ->groupBy('agent_id')
            ->pluck('under_review', 'agent_id');

        $input = [
            'agents' => Agent::all(),
            'underReviewByAgent' => $underReviewByAgent,
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

        // Send email using AgentEmailService (uses PHP mail() directly)
        Log::info('AgentController: About to send email using AgentEmailService', [
            'agent_id' => $agent->id,
            'email' => $agent->email,
            'method' => 'AgentEmailService::sendPasswordAssignmentEmail()',
        ]);

        $this->agentEmailService->sendPasswordAssignmentEmail($agent, $token);

        Log::info('AgentController: Email service called successfully', [
            'agent_id' => $agent->id,
            'email' => $agent->email,
        ]);

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
