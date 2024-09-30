<?php

namespace App\Http\Controllers\Admin;

use App\Exports\VaultExport;
use App\Models\Vault;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;

class VaultController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:vault.index')->only('index');
        $this->middleware('permission:vault.create')->only(['create', 'store']);
        $this->middleware('permission:vault.udpate')->only(['edit', 'udpate']);
        $this->middleware('permission:vault.delete')->only('destroy');
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $vault = Vault::first();

        return view('admin.vaults.index', compact('vault'));
    }



    public function export(Request $request)    
    {
        $ids = explode(',', $request->ids);
        return Excel::download(new VaultExport($ids), 'vault.xlsx');
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        abort(404);
        $input = [
            'method'    => 'POST',
            'action'    => route('vaults.store')
        ];

        return view('admin.vaults.create', $input);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'amount' => 'required|numeric',
            'note' => 'nullable|string',
            'type' => 'required|in:0,1'
        ]);

        Vault::create($request->all());

        return redirect()->route('vaults.index')
            ->with('success', __('alerts.added_successfully'));
    }


    public function edit(Vault $vault)
    {
        $input = [
            'method'    => 'PUT',
            'action'    => route('vaults.update', $vault->id),
            'vault'   => $vault,
        ];

        return view('admin.vaults.edit', $input);
    }


    public function update(Request $request, $id)
    {
        // Validate the request data
        $request->validate([
            // 'name' => 'required|string|max:255',
            'amount' => 'required|numeric',
            // 'note' => 'nullable|string',
            // 'type' => 'required|in:0,1'
        ]);

        // Find the vault transaction
        $transaction = Vault::findOrFail($id);

        // Update the transaction
        $transaction->update($request->all());

        return back()->with('success', __('main.transaction_updated_successfully'));
    }

    public function destroy(Vault $vault)
    {
        $vault->delete();
        return response()->json(['staus' => true, 'msg' => __('alerts.deleted_successfully')], 200);
    }
}
