<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Traits\ImagesTrait;
use App\Models\Superagent;
use App\Models\superagentTransaction;
use App\Exports\SuperagentTransactionExport;
use Maatwebsite\Excel\Facades\Excel;



class SuperagentTransactionController extends Controller
{
    use ImagesTrait;
    
    public function index(Request $request, $id)
    {
        $superagent = Superagent::findOrfail($id);
        $superagentTransactions = superagentTransaction::query();

        if ($request->filled('date_from')) {
            $superagentTransactions->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $superagentTransactions->whereDate('created_at', '<=', $request->date_to);
        }

        $superagentTransactions = $superagentTransactions->where('superagent_id', $id)->get();
        
        return view('admin.superagent_transactions.index', compact('superagent', 'superagentTransactions'));
    }
    
    public function create()
    {
        $superagents = Superagent::all();
        return view('admin.superagent_transactions.create', compact('superagents'));
    }
    
    public function store(Request $request, $id)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'amount' => 'required|numeric',
            'image' => 'required'
        ]);
        
        $superagent = Superagent::findOrFail($id);
        
        $data['superagent_id'] = $superagent->id;
        
        if($request->hasFile('image')) {
            $imageName = time() . '_transaction.' . $request->image->extension();
            $this->uploadImage($request->image, $imageName, 'banks');
            $data['image'] = 'Admin/images/banks/' .  $imageName;
        }
        
        
        superagentTransaction::create($data);
        
        if($superagent->wallet < $request->amount) {
            return to_route('superagent_transactions.index', $id)->with('error', __('main.bank_wallet_does_not_have_enough_amount'));
        }
        
        $superagent->wallet = $superagent->wallet - $request->amount;
        $superagent->save();
        
        return redirect()->route('superagent_transactions.index', $id)->with('success', __('alerts.added_successfully'));
        
    }

    public function export(Request $request, $id)
    {

        $ids = explode(',', $request->ids);
        return Excel::download(new SuperagentTransactionExport($ids), 'superagent_transactions.xlsx');
    }

    

    public function destroy($id)
    {
        $shipment = superagentTransaction::findOrFail($id);

        $shipment->delete();

        return response()->json(['staus' => true, 'msg' => __('alerts.deleted_successfully')], 200);
    }
}
