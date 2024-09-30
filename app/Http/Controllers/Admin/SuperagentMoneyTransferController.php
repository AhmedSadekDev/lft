<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Superagent;
use App\Models\MoneyTransfer;
use App\Services\SaveNotification;
use App\Services\SendNotification;
use App\Models\AppNotification;
use App\Models\Vault;

class SuperagentMoneyTransferController extends Controller
{
    public function index()
    {
        $input = [
            'financial_custody_superagents'     => MoneyTransfer::where('transfered_type', 'App\Models\Superagent')->get(),
            'route_create'      => route('financial_custody_superagents.create')
        ];

        return view('admin.financial_custody_superagents.index', $input);
    }

    public function create()
    {
        $input = [
            'superagents'         => Superagent::pluck('name', 'id'),
            'method'            => 'POST',
            'action'            => route('financial_custody_superagents.store'),
        ];


        return view('admin.financial_custody_superagents.create', $input);
    }

    public function store(Request $request)
    {
        $request->validate([
            'value'                  => ['required', 'numeric'],
            'superagent_id'   => ['required', 'exists:superagents,id'],
        ]);

        $vault = Vault::first();

        if ($request->value > $vault->amount) {
            return back()->with('error', __("main.you dont have enougth money"));
        }

        $superagent = Superagent::find($request->superagent_id);
        $superagent->update(['wallet' => $superagent->wallet + $request->value]);
        $vault->update(['amount' => $vault->amount - $request->value]);

        $data["value"] = $request->value;
        $data["transfered_type"] = "App\Models\Superagent";
        $data["transfered_id"] = $request->superagent_id;
        $data["transferer_type"] = "App\Models\User";
        $data["transferer_id"] = auth()->id();
        $data["type"] = 1;
        
        MoneyTransfer::create($data);
       $value = $request->value;
        
        $title = __('new_notification');
        $text = __('daily_financial_custody_added', [
            'value' => $value,
            'agent' => $superagent->name
        ]);

        SaveNotification::create($title, $text, $superagent->id, Superagent::class, AppNotification::specific);
        SendNotification::send($superagent->device_token ?? "", $title, $text);

        return redirect()->route('financial_custody_superagents.index')->with(['success' => __('alerts.added_successfully')]);
    }

    public function destroy(Request $request, $id)
    {
        $MoneyTransfer = MoneyTransfer::find($id);
        $MoneyTransfer->delete();
        return response()->json(['staus' => true, 'msg' => __('alerts.deleted_successfully')], 200);
    }
}
