<?php

namespace App\Http\Controllers\Admin;

use App\Models\Company;
use Mpdf\Mpdf;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Bank;
use App\Models\Booking;
use Illuminate\Support\Facades\Http;

class InvoicesController extends Controller
{
    public function index(Request $request, $id = null)
    {
        $company = null;
        if ($id || $request->filled('id')) {
            $company = Company::findOrFail($request->id ?? $id);
        }

        $bookings = Booking::query();

        if ($request->filled('date_from')) {
            $bookings->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $bookings->whereDate('created_at', '<=', $request->date_to);
        }

        if ($id || $request->filled('id')) {
            $bookings->where('company_id', $request->id ?? $id);
        }
        
        // Initialize an empty collection to hold all payments
        $allPayments = collect();
        
        // Loop through each booking for the company
        foreach ($company->bookings as $booking) {
            // Retrieve the invoice associated with the booking
            $invoice = $booking->invoice;
        
            // Retrieve and merge the invoice payments into the collection
            if ($invoice) {
                $payments = $invoice->invoicePayments;
                $allPayments = $allPayments->merge($payments);
            }
        }
        
        // $allPayments now contains all invoice payments for the company's bookings


        $bookings = $bookings->whereHas('invoice')->get();
        $companies = Company::where('taxed', 1)->get();
        $banks = Bank::all();
        
        

        return view('admin.invoices.index', compact('bookings', 'companies', 'banks', 'allPayments'));
    }



    public function export($id = null)
    {
        $company = Company::findOrFail($id);

        $bookings = Booking::all();

        return view('admin.invoices.pdf', compact('bookings'));
    }


    public function downloadPDF(Request $request, $id = null)
    {
        $ids = explode(',', $request->ids);
        $bookings = Booking::whereIn('id', $ids)->get();
        $html = view('admin.invoices.pdf', compact('bookings'))->render();

        // Configure Mpdf for RTL support and include necessary fonts
        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'default_font' => 'dejavusans',
            'directionality' => 'rtl',
            'margin_left' => 10,
            'margin_right' => 10,
            'margin_top' => 10,
            'margin_bottom' => 10,
            'margin_header' => 6,
            'margin_footer' => 6
        ]);


        $mpdf->WriteHTML($html);

        return $mpdf->Output('invoice.pdf', 'D');
    }
}
