<?php

namespace App\Http\Controllers\Admin;

use App\Models\Car;
use App\Models\Agent;
use Illuminate\Http\Request;
use App\Models\AgentCarTranfer;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\AgentCarTranferExport;
use App\Http\Traits\ImagesTrait;


class AgentCarTranferController extends Controller
{
    use ImagesTrait;

    public function index(Request $request, $id)
    {
        $agent = Agent::findOrFail($id);

        $items = AgentCarTranfer::query();


        if($request->filled('date_from')) {
            $items->whereDate('created_at', '>=', $request->date_from);
        }

        if($request->filled('date_to')) {
            $items->whereDate('created_at', '<=', $request->date_to);
        }

        $items = $items->where('agent_id', $agent->id)->get();

        return view('admin.agents_car_tranfer.index', compact('agent', 'items'));
    }

    public function export(Request $request, $id)
    {
        $ids = explode(',', $request->ids);
        return Excel::download(new AgentCarTranferExport($ids), 'agents_car_tranfer.xlsx');
    }


    public function create($id)
    {
        $agent = Agent::findOrFail($id);

        $cars = Car::all();

        return view('admin.agents_car_tranfer.create', compact('agent', 'cars'));
    }


    public function store(Request $request)
    {
        $data = $request->validate([
            'agent_id' =>'required|exists:agents,id',
            'car_id' =>'required|exists:cars,id',
            'name' =>'required|string|max:255',
            'value' =>'required|numeric',
            'image' => 'required'
        ]);

        $data['user_id'] = auth()->user()->id;
        
        if($request->hasFile('image')) {
            $imageName = time() . '_transaction.' . $request->image->extension();
            $this->uploadImage($request->image, $imageName, 'banks');
            $data['image'] = 'Admin/images/banks/' .  $imageName;
        }


        $agent = Agent::find($data['agent_id']);

        $car = Car::findOrFail($data['car_id']);

        $agentwallet = $agent->wallet;

        if($request->value > $agentwallet) {
            return redirect()->back()->with('error', __('main.you dont have enougth money'));
        }

        $agent->update(['wallet'=> $agentwallet - $request->value]);
        $car->update(['wallet'=> $car->wallet + $request->value]);

        $agent->agentCarTransfers()->create($data);

        return redirect()->route('agent_car_tranfer.index', $agent->id)->with('success', __('alerts.added_successfully'));
    }

    public function edit($id)
    {
        $item = AgentCarTranfer::findOrFail($id);

        $cars = Car::all();

        return view('admin.agents_car_tranfer.edit', compact('cars', 'item'));
    }


    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'car_id' =>'required|exists:cars,id',
            'name' =>'required|string|max:255',
            'value' =>'required|numeric'
        ]);

        $item = AgentCarTranfer::findOrFail($id);
        
        if($request->hasFile('image')) {
            $imageName = time() . '_transaction.' . $request->image->extension();
            $this->uploadImage($request->image, $imageName, 'banks', $item->image);
            $data['image'] = 'Admin/images/banks/' .  $imageName;
        }

        $oldValue = $item->value;
        $newValue = $request->value;
        $agent = $item->agent;


        if ($oldValue > $newValue) {
            $diff = $oldValue - $newValue;
            $agent->update(['wallet' => $agent->wallet + $diff]);
        }

        if ($newValue > $oldValue) {
            $diff = $newValue - $oldValue;

            if ($agent->wallet - $diff < 0) {
                return redirect()->back()->with('error', __('main.you dont have enougth money'));
            }

            $agent->update(['wallet' => $agent->wallet - $diff]);
        }

        $item->update($data);


        return redirect()->route('agent_car_tranfer.index', $agent->id)->with('success', __('alerts.updated_successfully'));
    }

    public function destroy($id)
    {
        $item = AgentCarTranfer::findOrFail($id);

        $item->delete();

        return response()->json(['staus' => true, 'msg' => __('alerts.deleted_successfully')], 200);
    }
}
