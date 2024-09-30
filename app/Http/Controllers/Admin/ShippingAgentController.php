<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\shippingAgentsRequest;
use App\Models\shippingAgent;
use Illuminate\Http\Request;

class ShippingAgentController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:shippingAgents.index')->only('index');
        $this->middleware('permission:shippingAgents.create')->only(['create', 'store']);
        $this->middleware('permission:shippingAgents.udpate')->only(['edit', 'udpate']);
        $this->middleware('permission:shippingAgents.delete')->only('destroy');
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $input = [
            'shippingAgents' => shippingAgent::all(),
        ];

        return view('admin.shippingAgents.index', $input);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $input = [
            'method' => 'POST',
            'action' => route('shippingAgents.store'),
        ];

        return view('admin.shippingAgents.create', $input);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(shippingAgentsRequest $request)
    {
        shippingAgent::create($request->validated());
        return redirect()->route('shippingAgents.index')->with('success', __('alerts.added_successfully'));
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\shippingAgent  $shippingAgent
     * @return \Illuminate\Http\Response
     */
    public function show(shippingAgent  $shippingAgent)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\shippingAgent  $shippingAgent
     * @return \Illuminate\Http\Response
     */
    public function edit(shippingAgent $shippingAgent)
    {
        $input = [
            'shippingAgent' => $shippingAgent,
            'method' => 'PUT',
            'action' => route('shippingAgents.update', $shippingAgent),
        ];

        return view('admin.shippingAgents.edit', $input);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\shippingAgent  $shippingAgent
     * @return \Illuminate\Http\Response
     */
    public function update(shippingAgentsRequest $request, shippingAgent  $shippingAgent)
    {
        $shippingAgent->update($request->all());
        return redirect()->route('shippingAgents.index')->with('success', __('alerts.updated_successfully'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\shippingAgent  $shippingAgent
     * @return \Illuminate\Http\Response
     */
    public function destroy(shippingAgent  $shippingAgent)
    {
        $shippingAgent->delete();
        return response()->json(['staus' => true, 'msg' => __('alerts.deleted_successfully')], 200);
    }
}
