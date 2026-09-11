<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\VaultTransaction;
use App\Exports\VaultTransactionExport;
use Maatwebsite\Excel\Facades\Excel;


class VaultTransactionController extends Controller
{

    public function index(Request $request)
    {
        $vaulttransactionsQuery = VaultTransaction::with(['bank', 'agient']);

        if ($request->filled('from')) {
            $vaulttransactionsQuery->where('created_at', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $vaulttransactionsQuery->where('created_at', '<=', $request->to);
        }

        $vaulttransactions = $vaulttransactionsQuery->where('type', 0)->latest()->get();

        return view('admin.vaulttransactions.index', compact('vaulttransactions'));
    }

    public function export(Request $request)
    {

        $ids = explode(',', $request->ids);
        return Excel::download(new VaultTransactionExport($ids), 'transactions.xlsx');
    }

    public function edit($id)
    {
        $item = VaultTransaction::findOrFail($id);

        return view('admin.vaulttransactions.edit', compact('item'));
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'amount' => 'required|numeric',
        ]);

        $item = VaultTransaction::findOrFail($id);
        $item->update($data);

        return to_route('vaultransactions.index')->with('success', __('alerts.updated_successfully'));
    }

    public function destroy($id)
    {
        $shipment = VaultTransaction::findOrFail($id);

        $shipment->delete();

        return response()->json(['staus' => true, 'msg' => __('alerts.deleted_successfully')], 200);
    }
}
