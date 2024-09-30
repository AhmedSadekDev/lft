<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Invoice;
use Illuminate\Http\Request;
use App\Exports\CompanyExport;
use App\Models\InvoicePayment;
use App\Http\Traits\ImagesTrait;
use App\Models\BankTrnsaction;
use Maatwebsite\Excel\Facades\Excel;

class InvoicePaymentController extends Controller
{
    use ImagesTrait;
    public function index($id)
    {
        $invoice = Invoice::findOrFail($id);

        $payments = $invoice->invoicePayments;

        return view('admin.invoicepayments.index', compact('invoice', 'payments'));
    }


    public function store(Request $request)
    {
        
        $data = $request->validate([
            'bank_id' => 'required|exists:banks,id',
            'invoice_id' => 'required|exists:invoices,id',
            'value' => 'required|numeric',
            'image' => 'nullable|mimes:png,jpg,jpeg'
        ]);


        $invoice = Invoice::findOrFail($request->invoice_id);
        
        $invoiceTotalBeforeTax = $invoice->invoice_total_before_tax;
        $vatValue = $invoice->value_added_tax_amount; // Already calculated in the model
        $saleValue = $invoice->sales_tax_amount; // Already calculated in the model
        $discountValue = $invoice->discount_amount; // Fixed discount, not a percentage
    
        $taxedServicesTotal = $invoice->taxed_services_total_before_vat ?? 0;
        $untaxedServicesTotal = $invoice->untaxed_services_total_before_vat ?? 0;
        $transportationTotal = $invoice->transportation_total_before_vat ?? 0;
    
        $finalValue = $invoiceTotalBeforeTax + $taxedServicesTotal + $untaxedServicesTotal + $vatValue - $saleValue - $discountValue;
        $remain = $finalValue - ($invoice->invoicePayments->sum('value') ?? 0);
        
        if ($request->value > $remain) {
            return back()->with('error', 'المبلغ المتبقى '. $remain);
        }
        
        
        
        if ($request->hasFile('image')) {
            $imageName = time() . '_transaction.' . $request->image->extension();
            $imagePath = $request->type == 1 ? 'companyInvoice' : 'banks';
            $this->uploadImage($request->image, $imageName, $imagePath);
            $data['image'] = "Admin/images/{$imagePath}/{$imageName}";
        }

    

        $invoice->invoicePayments()->create($data);

        BankTrnsaction::create([
            'bank_id' => $request->bank_id,
            'user_id' => auth()->user()->id,
            'name' => 'دفع حساب شركه ' . $invoice->booking->company->name,
            'type' => 1,
            'amount' => $request->value,
            'image' => $data['image'],
            'company_id' => $invoice->booking->company_id
        ]);

        return back()->with('success', __("alerts.added_successfully"));
    }


    public function update(Request $request, $id)
    {
        $payment = InvoicePayment::findOrFail($id);
        $data = $request->except('image');

        if ($request->hasFile('image')) {
            $imageName = time() . '_transaction.' . $request->image->extension();
            $imagePath = $request->type == 1 ? 'companyInvoice' : 'banks';
            $this->uploadImage($request->image, $imageName, $imagePath);
            $data['image'] = "Admin/images/{$imagePath}/{$imageName}";
        }

        $payment->update($data);

        return back()->with('success', __("alerts.updated_successfully"));
    }


    public function excel($id)
    {
        $company = Company::findOrFail($id);

        return Excel::download(new CompanyExport($id), 'company_invoices.xlsx');
    }


    public function pdf($id)
    {
        $company = Company::findOrFail($id);

        $bookings = $company->bookings;

        return view('admin.invoices.pdf', compact('bookings'));
    }


    public function destroy($id)
    {
    }
}
