<?php

namespace App\Http\Controllers\Admin;

use App\Exports\companyInvoicesExport;
use App\Http\Controllers\Controller;
use App\Http\Traits\ImagesTrait;
use App\Models\Company;
use App\Models\companyInvoices;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class companyInvoiceController extends Controller
{
    use ImagesTrait;
    public function index(Request $request, $id)
    {
        $company = Company::findOrfail($id);
        $invoices = companyInvoices::query();

        if ($request->filled('from')) {
            $invoices->whereDate('created_at', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $invoices->whereDate('created_at', '<=', $request->to);
        }

        $invoices = $invoices->where('company_id', $id)->get();

        return view('admin.companyInvoice.index', compact('company', 'invoices'));
    }

    public function export($from, $to, $company_id)
    {
        $company = Company::findOrFail($company_id);
        return Excel::download(new companyInvoicesExport($from, $to, $company_id), $company->name . '.xlsx');
    }

    public function create($id)
    {

        return view('admin.companyInvoice.create', compact('id'));
    }


    public function edit($id)
    {
        $invoice = companyInvoices::findOrfail($id);

        return view('admin.companyInvoice.edit', compact('invoice'));
    }


    public function store(Request $request)
    {
        $data = $request->validate([
            'image' => 'required|file|mimes:png,jpg,jpeg,gif,pdf',
            'total' => 'required|numeric',
            'company_id' => 'required|exists:companies,id'
        ]);
        $company = Company::find($request->company_id);
        if ($company->wallet < $request->total) {
            return back()->with('error', 'الرصيد المتاح للشركه غير كافي');
        }
        $company->update(['wallet' => $company->wallet - $request->total]);
        $imageName = time() . '_invoice.' . $request->image->extension();
        $this->uploadImage($request->image, $imageName, 'companyInvoice');
        $data['user_id'] = auth()->user()->id;
        $data['image'] = $imageName;
        $data['type'] = 1;

        companyInvoices::create($data);

        return to_route('companyInvoice.index', $request->company_id)->with('success', __('alerts.added_successfully'));
    }

    public function update(Request $request, $id)
    {
        $invoice = companyInvoices::findOrFail($id);
        $data = $request->validate([
            'image' => 'nullable|file|mimes:png,jpg,jpeg,gif,pdf',
            'total' => 'required|numeric',
            'invoice_id' => 'required|exists:company_invoices,id'
        ]);
        $data['user_id'] = auth()->user()->id;
        if ($request->image) {
            $imageName = time() . '_invoice.' . $request->image->extension();
            $this->uploadImage($request->image, $imageName, 'companyInvoice');
            $data['image'] = $imageName;
        }

        $invoice->update($data);

        return to_route('companyInvoice.index', $invoice->company_id)->with('success', __('alerts.updated_successfully'));
    }


    public function destroy($id)
    {
        $invoice = companyInvoices::findOrFail($id);
        $company = Company::find($invoice->company_id);
        $company->update(['wallet' => $company->wallet + $invoice->total]);
        $invoice->delete();

        return response()->json(['staus' => true, 'msg' => __('alerts.deleted_successfully')], 200);
    }
}
