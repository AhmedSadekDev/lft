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

        // type 0 = صرف / type 1 = وارد (مثل إرجاع مندوب بعد الموافقة)
        $vaulttransactions = $vaulttransactionsQuery->latest()->get();

        return view('admin.vaulttransactions.index', compact('vaulttransactions'));
    }

    public function export(Request $request)
    {
        $ids = $request->filled('ids') ? explode(',', $request->ids) : null;

        return Excel::download(
            new VaultTransactionExport($ids, $request),
            'vault-transactions-' . now()->format('Y-m-d') . '.xlsx'
        );
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
