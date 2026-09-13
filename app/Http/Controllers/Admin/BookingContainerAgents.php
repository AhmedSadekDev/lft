<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Agent;
use App\Models\BookingContainer;

class BookingContainerAgents extends Controller
{
    public function index()
    {
        $containers = BookingContainer::all();
        
        return view('admin.bookingsagents.index', compact('containers'));
    }
    
    
    public function edit($id)
    {
        $container = BookingContainer::findOrFail($id);
        $stageType = (int) request('type_id', app(\App\Services\ContainerStageService::class)->currentType($container));
        abort_unless(in_array($stageType, [0, 1, 2], true), 422);
        $container->load(['agents' => fn ($q) => $q->where('booking_container_agents.stage_type', $stageType)]);
        $agents = Agent::all();
        
        return view('admin.bookingsagents.edit', compact('container', 'agents', 'stageType'));
        
    }
    
    
    public function update(Request $request, $id)
    {
        $container = BookingContainer::findOrFail($id);
        
        $request->validate(['agents' => 'sometimes|array', 'agents.*' => 'integer|exists:agents,id', 'type_id' => 'sometimes|integer|in:0,1,2']);
        app(\App\Services\ContainerStageService::class)->assign($container->id, $request->input('agents', []), $request->filled('type_id') ? (int) $request->type_id : null);
        
        return redirect()->route('booking_containers_agents.index', $id)->with('alert', 'updated_successfully');
    }

}
