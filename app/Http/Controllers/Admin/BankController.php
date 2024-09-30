<?php

namespace App\Http\Controllers\Admin;

use App\Models\Bank;
use App\Exports\BankExport;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;

class BankController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:bank.index')->only('index');
        $this->middleware('permission:bank.create')->only(['create', 'store']);
        $this->middleware('permission:bank.udpate')->only(['edit', 'udpate']);
        $this->middleware('permission:bank.delete')->only('destroy');
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $banks = Bank::get();
        return view('admin.banks.index', compact('banks'));
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
            'action'    => route('banks.store')
        ];

        return view('admin.banks.create', $input);
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
        ]);

        Bank::create($request->all());

        return redirect()->route('banks.index')
            ->with('success', __('alerts.added_successfully'));
    }


    public function edit(Bank $bank)
    {
        $input = [
            'method'    => 'PUT',
            'action'    => route('banks.update', $bank->id),
            'bank'   => $bank,
        ];

        return view('admin.banks.edit', $input);
    }


    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'amount' => 'required|numeric',
        ]);

        $bank = Bank::findOrFail($id);

        $bank->update($request->all());

        return redirect()->route('banks.index')->with('success', __('alerts.updated_successfully'));
    }

    public function destroy(Bank $bank)
    {
        $bank->delete();
        return response()->json(['staus' => true, 'msg' => __('alerts.deleted_successfully')], 200);
    }
}
