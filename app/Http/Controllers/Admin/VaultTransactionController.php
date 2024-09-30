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
        $vaulttransactions = VaultTransaction::query();

        if ($request->filled('date_from')) {
            $vaulttransactions->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $vaulttransactions->whereDate('created_at', '<=', $request->date_to);
        }

        $vaulttransactions = $vaulttransactions->get();

        return view('admin.vaulttransactions.index', compact('vaulttransactions'));
    }

    public function export(Request $request)
    {

        $ids = explode(',', $request->ids);
        return Excel::download(new VaultTransactionExport($ids), 'transactions.xlsx');
    }

    public function destroy($id)
    {
        $shipment = VaultTransaction::findOrFail($id);

        $shipment->delete();

        return response()->json(['staus' => true, 'msg' => __('alerts.deleted_successfully')], 200);
    }
}
