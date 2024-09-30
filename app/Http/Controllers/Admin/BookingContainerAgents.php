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
        $agents = Agent::all();
        
        return view('admin.bookingsagents.edit', compact('container', 'agents'));
        
    }
    
    
    public function update(Request $request, $id)
    {
        $container = BookingContainer::findOrFail($id);
        
        $container->agents()->sync($request->agents);
        
        return redirect()->route('booking_containers_agents.index', $id)->with('alert', 'updated_successfully');
    }

}
